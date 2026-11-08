<?php

namespace App\Services\Admin\Monitoring;

use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\Aum\FundDataCoverage;
use App\Services\Crons\FundDataBatchDispatcher;
use App\Services\Crons\FundDataScheduleProvisioner;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Manual fund data run (admin Pipeline Health)
|--------------------------------------------------------------------------
|
| Lets an admin re-run the AUM / NAV scrapers right away (e.g. after fixing
| a scraper) instead of waiting for each ETF's scheduled hour. Queues the
| same RunScheduledSecurityUpdateJob jobs as the hourly run, via
| FundDataBatchDispatcher, so a queue worker must be running.
|
| Scopes (covered ETFs only: active, issuer has a scraper, active FUND_DATA
| schedule):
|   problems  stale, awaiting first data, or latest scrape attempt failed
|             (the "ETFs needing attention" list on Pipeline Health)
|   issuer    every covered ETF of one issuer
|   all       every covered ETF
|
| ETFs whose issuer has a scraper but that were never given a FUND_DATA
| schedule are included in every scope: a schedule is created for them
| first (FundDataScheduleProvisioner), so they are scraped now and by the
| hourly run from then on.
|
| Only one manual run may be in progress at a time. A run that has not
| finished within STALE_AFTER_MINUTES (e.g. no queue worker) no longer
| blocks new runs.
|
*/

class ManualFundDataRunService
{
    public const SCOPES = ['problems', 'issuer', 'all'];

    public const NOTES_PREFIX = 'Manual fund data run';

    public const STALE_AFTER_MINUTES = 60;

    private const MAX_FAILURES_LISTED = 50;

    public function __construct(
        private FundDataBatchDispatcher $dispatcher,
        private FundDataScheduleProvisioner $provisioner,
    ) {}

    /**
     * @throws \DomainException when a run is in progress or nothing matches
     */
    public function start(string $scope, ?int $issuerId = null): array
    {
        if (! in_array($scope, self::SCOPES, true)) {

            throw new \InvalidArgumentException("Unknown scope: {$scope}");
        }

        if ($running = $this->runningBatch()) {

            throw new \DomainException(
                "A manual run is already in progress ({$running->processed_count} of {$running->total_securities} done)."
            );
        }

        // Never-scheduled ETFs in this scope get a schedule first.
        $scheduled = $this->provisioner->provision(
            $this->unscheduledInScope($scope, $issuerId)
        );

        $securityIds = $this->securityIds($scope, $issuerId);

        // One FUND_DATA schedule per security (the job needs a schedule).
        $schedules = SecurityUpdateSchedule::query()
            ->where('security_update_type_id', SecurityUpdateType::FUND_DATA)
            ->where('status_id', Status::ACTIVE)
            ->whereIn('security_id', $securityIds ?: [0])
            ->orderBy('id')
            ->get()
            ->unique('security_id')
            ->values();

        if ($schedules->isEmpty()) {

            throw new \DomainException('There are no ETFs to update for this selection.');
        }

        $batch = $this->dispatcher->dispatch(
            $schedules,
            self::NOTES_PREFIX." ({$this->scopeLabel($scope, $issuerId)}) from Pipeline Health. ".
                "Total updates: {$schedules->count()}"
        );

        return $this->progress($batch->fresh()) + [
            'schedules_created' => $scheduled->count(),
        ];
    }

    private function unscheduledInScope(string $scope, ?int $issuerId)
    {
        return FundDataCoverage::unscheduledSecurities()
            ->when(
                $scope === 'issuer',
                fn ($query) => $query->whereExists(function ($exists) use ($issuerId) {
                    $exists->selectRaw('1')
                        ->from('security_details')
                        ->whereColumn('security_details.security_id', 'securities.id')
                        ->where('security_details.etf_issuer_id', $issuerId);
                })
            )
            ->get();
    }

    /**
     * Most recent manual run within the last day, if any.
     */
    public function latest(): ?array
    {
        $batch = $this->manualBatches()
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->first();

        return $batch ? $this->progress($batch) : null;
    }

    public function find(int $id): ?array
    {
        $batch = $this->manualBatches()->find($id);

        return $batch ? $this->progress($batch) : null;
    }

    public function progress(SecurityIngestionBatch $batch): array
    {
        $failures = DB::table('security_ingestion_batch_items AS i')
            ->join('securities AS s', 's.id', '=', 'i.security_id')
            ->where('i.security_ingestion_batch_id', $batch->id)
            ->where('i.status_id', Status::FAILED)
            ->orderBy('s.symbol')
            ->limit(self::MAX_FAILURES_LISTED)
            ->get(['s.symbol', 'i.error_message'])
            ->map(fn ($row) => ['symbol' => $row->symbol, 'error' => $row->error_message])
            ->all();

        $isComplete = $batch->processed_count >= $batch->total_securities;

        return [
            'id' => $batch->id,
            'description' => $this->describe((string) $batch->processing_notes),
            'total' => (int) $batch->total_securities,
            'processed' => (int) $batch->processed_count,
            'succeeded' => (int) $batch->success_count,
            'failed' => (int) $batch->failure_count,
            'started_at' => $batch->started_at?->toIso8601String(),
            'completed_at' => $batch->completed_at?->toIso8601String(),
            'is_complete' => $isComplete,
            'is_stalled' => ! $isComplete
                && $batch->created_at->lt(now()->subMinutes(self::STALE_AFTER_MINUTES)),
            'failures' => $failures,
        ];
    }

    /**
     * "Manual fund data run (issuer: Kurv) from Pipeline Health. ..." ->
     * "Manual run: issuer: Kurv". (FinalizeScheduledUpdatesBatchJob appends
     * the outcome to the notes; the prefix is kept so runs stay findable.)
     */
    private function describe(string $notes): string
    {
        return preg_match('/^'.preg_quote(self::NOTES_PREFIX, '/').' \((.*?)\)/', $notes, $m)
            ? "Manual run: {$m[1]}"
            : $notes;
    }

    private function manualBatches()
    {
        return SecurityIngestionBatch::query()
            ->where('processing_notes', 'like', self::NOTES_PREFIX.'%');
    }

    private function runningBatch(): ?SecurityIngestionBatch
    {
        return $this->manualBatches()
            ->whereColumn('processed_count', '<', 'total_securities')
            ->where('created_at', '>=', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->latest('id')
            ->first();
    }

    /**
     * @return int[]
     */
    private function securityIds(string $scope, ?int $issuerId): array
    {
        $covered = FundDataCoverage::coveredSecurities()
            ->with('detail')
            ->get();

        if ($scope === 'issuer') {

            return $covered
                ->filter(fn (Security $security) => $issuerId !== null
                    && $security->detail?->etf_issuer_id === $issuerId)
                ->pluck('id')
                ->all();
        }

        if ($scope === 'all') {

            return $covered->pluck('id')->all();
        }

        // problems: same rule as the Pipeline Health "needing attention" list.
        $latestDates = FundDataCoverage::latestAumDates();

        $attempts = FundDataCoverage::latestScrapeAttempts($covered->pluck('id')->all());

        return $covered
            ->filter(fn (Security $security) => FundDataCoverage::status(true, $latestDates[$security->id] ?? null) !== FundDataCoverage::CURRENT
                || FundDataCoverage::latestAttemptFailed($attempts[$security->id] ?? []))
            ->pluck('id')
            ->all();
    }

    private function scopeLabel(string $scope, ?int $issuerId): string
    {
        return match ($scope) {
            'problems' => 'ETFs needing attention',
            'all' => 'all covered ETFs',
            'issuer' => 'issuer: '.(EtfIssuer::find($issuerId)?->etf_issuer_name ?? "#{$issuerId}"),
        };
    }
}
