<?php

namespace App\Services\Aum;

use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Fund data coverage
|--------------------------------------------------------------------------
|
| An ETF is "covered" when it is active, its issuer has a fund data
| scraper (AiSecurityFundDataExtractionService::SCRAPERS) and it has an
| active FUND_DATA update schedule.
|
| Public coverage status:
|   current      has AUM data updated within STALE_AFTER_DAYS
|   stale        has AUM data, but none within STALE_AFTER_DAYS
|   pending      covered, but no AUM data collected yet
|   not_covered  not covered and no AUM data
|
| An ETF with AUM data is shown even if it is no longer covered (its data
| simply goes stale).
|
*/

class FundDataCoverage
{
    public const CURRENT = 'current';

    public const STALE = 'stale';

    public const PENDING = 'pending';

    public const NOT_COVERED = 'not_covered';

    public const STALE_AFTER_DAYS = 7;

    /**
     * Active securities that the daily scraper run will update.
     */
    public static function coveredSecurities(): Builder
    {
        return Security::query()
            ->where('securities.status_id', Status::ACTIVE)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_details')
                    ->whereColumn('security_details.security_id', 'securities.id')
                    ->whereIn(
                        'security_details.etf_issuer_id',
                        AiSecurityFundDataExtractionService::supportedIssuerIds()
                    );
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_update_schedules')
                    ->whereColumn('security_update_schedules.security_id', 'securities.id')
                    ->where('security_update_schedules.security_update_type_id', SecurityUpdateType::FUND_DATA)
                    ->where('security_update_schedules.status_id', Status::ACTIVE);
            });
    }

    /**
     * Active securities whose issuer has a scraper but which have no
     * FUND_DATA schedule at all, so the daily run never picks them up.
     * (A schedule that exists but was deactivated is left alone.)
     */
    public static function unscheduledSecurities(): Builder
    {
        return Security::query()
            ->where('securities.status_id', Status::ACTIVE)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_details')
                    ->whereColumn('security_details.security_id', 'securities.id')
                    ->whereIn(
                        'security_details.etf_issuer_id',
                        AiSecurityFundDataExtractionService::supportedIssuerIds()
                    );
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_update_schedules')
                    ->whereColumn('security_update_schedules.security_id', 'securities.id')
                    ->where('security_update_schedules.security_update_type_id', SecurityUpdateType::FUND_DATA);
            });
    }

    public static function isCovered(Security $security): bool
    {
        return self::coveredSecurities()
            ->whereKey($security->id)
            ->exists();
    }

    /**
     * @return array<int, string> security_id => latest aum_date (Y-m-d)
     */
    public static function latestAumDates(): array
    {
        return SecurityAumHistory::query()
            ->selectRaw('security_id, MAX(aum_date) AS latest_date')
            ->groupBy('security_id')
            ->pluck('latest_date', 'security_id')
            ->map(fn ($date) => (string) $date)
            ->all();
    }

    public static function isStale(?string $latestDate): bool
    {
        if (! $latestDate) {

            return false;
        }

        return CarbonImmutable::parse($latestDate)
            ->lt(CarbonImmutable::today()->subDays(self::STALE_AFTER_DAYS));
    }

    public static function status(bool $covered, ?string $latestDate): string
    {
        if ($latestDate) {

            return self::isStale($latestDate) ? self::STALE : self::CURRENT;
        }

        return $covered ? self::PENDING : self::NOT_COVERED;
    }

    /**
     * Whether the security can be scraped right now: active, and its
     * issuer has a fund data scraper. (Unlike coveredSecurities(), a
     * FUND_DATA schedule is not required.)
     */
    public static function hasScraper(Security $security): bool
    {
        return (int) $security->status_id === Status::ACTIVE
            && in_array(
                $security->detail?->etf_issuer_id,
                AiSecurityFundDataExtractionService::supportedIssuerIds(),
                true
            );
    }

    /**
     * Last success and last final failure per security (FUND_DATA only).
     *
     * @return array<int, array{last_success_at: ?string, last_failure_at: ?string, last_error: ?string}>
     */
    public static function latestScrapeAttempts(array $securityIds): array
    {
        if (empty($securityIds)) {

            return [];
        }

        $base = fn () => DB::table('security_ingestion_batch_items')
            ->where('security_update_type_id', SecurityUpdateType::FUND_DATA)
            ->whereIn('security_id', $securityIds);

        $successes = $base()
            ->where('is_success', true)
            ->groupBy('security_id')
            ->selectRaw('security_id, MAX(completed_at) AS last_at')
            ->pluck('last_at', 'security_id');

        $latestFailureIds = $base()
            ->where('status_id', Status::FAILED)
            ->groupBy('security_id')
            ->selectRaw('MAX(id) AS id')
            ->pluck('id');

        $failures = DB::table('security_ingestion_batch_items')
            ->whereIn('id', $latestFailureIds)
            ->get(['security_id', 'completed_at', 'error_message'])
            ->keyBy('security_id');

        $result = [];

        foreach ($securityIds as $id) {

            $failure = $failures->get($id);

            $result[$id] = [
                'last_success_at' => $successes[$id] ?? null,
                'last_failure_at' => $failure?->completed_at,
                'last_error' => $failure?->error_message,
            ];
        }

        return $result;
    }

    public static function latestAttemptFailed(array $attempt): bool
    {
        $lastFailure = $attempt['last_failure_at'] ?? null;

        $lastSuccess = $attempt['last_success_at'] ?? null;

        return (bool) $lastFailure
            && (! $lastSuccess || $lastFailure > $lastSuccess);
    }
}
