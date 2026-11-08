<?php

namespace App\Queries\Admin\Monitoring;

use App\Models\CronLog;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\Aum\FundDataCoverage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Pipeline Health
|--------------------------------------------------------------------------
|
| One-screen answer to "is the AUM data flowing?" for the admin area:
|
|   coverage  covered ETFs by status (current / stale / pending) and how
|             many were successfully scraped in the last 24 hours
|   issuers   per-issuer scrape success over the last 7 days, so a broken
|             scraper (issuer site redesign) shows up as a whole issuer
|   problems  covered ETFs that are stale, pending, or whose latest scrape
|             attempt failed, with the last error
|   crons     last run of each scheduled command, and whether it is overdue
|
| Scrape attempts come from security_ingestion_batch_items (FUND_DATA).
| Cron runs come from cron_logs.
|
*/

class PipelineHealthQuery
{
    /*
    | Scheduled commands to watch, with the longest acceptable gap between
    | runs. Keep in sync with routes/console.php (a test enforces this).
    */
    public const EXPECTED_CRONS = [
        'securities:run-scheduled-updates' => [
            'label' => 'Fund data scrapers',
            'schedule' => 'Hourly',
            'max_gap_hours' => 2,
        ],
        'app:cleanup-ai-pipeline-data' => [
            'label' => 'Pipeline data cleanup',
            'schedule' => 'Daily 05:15 UTC',
            'max_gap_hours' => 26,
        ],
        'app:trim-cron-logs' => [
            'label' => 'Trim cron logs',
            'schedule' => 'Weekly (Sun 04:00 UTC)',
            'max_gap_hours' => 24 * 7 + 2,
        ],
        'app:trim-import-logs' => [
            'label' => 'Trim import logs',
            'schedule' => 'Weekly (Sun 05:00 UTC)',
            'max_gap_hours' => 24 * 7 + 2,
        ],
    ];

    public const ISSUER_WINDOW_DAYS = 7;

    public function getData(): array
    {
        $now = CarbonImmutable::now();

        $covered = FundDataCoverage::coveredSecurities()
            ->with('detail.issuer')
            ->get()
            ->keyBy('id');

        $latestDates = FundDataCoverage::latestAumDates();

        $attempts = FundDataCoverage::latestScrapeAttempts($covered->keys()->all());

        // Have a scraper but no FUND_DATA schedule: never scraped. A manual
        // run creates their schedules (ManualFundDataRunService).
        $unscheduled = FundDataCoverage::unscheduledSecurities()
            ->with('detail.issuer')
            ->get();

        $coverage = $this->coverage($covered, $latestDates, $attempts, $now)
            + ['unscheduled' => $unscheduled->count()];

        $issuers = $this->issuers($covered, $latestDates, $attempts, $now);

        $problems = $this->problems($covered, $latestDates, $attempts, $unscheduled);

        $crons = $this->crons($now);

        return [
            'generated_at' => $now->toIso8601String(),
            'stale_after_days' => FundDataCoverage::STALE_AFTER_DAYS,
            'issuer_window_days' => self::ISSUER_WINDOW_DAYS,
            'overall' => $this->overall($coverage, $issuers, $crons),
            'coverage' => $coverage,
            'issuers' => $issuers,
            'problems' => $problems,
            'crons' => $crons,
        ];
    }

    private function coverage($covered, array $latestDates, array $attempts, CarbonImmutable $now): array
    {
        $counts = [
            FundDataCoverage::CURRENT => 0,
            FundDataCoverage::STALE => 0,
            FundDataCoverage::PENDING => 0,
        ];

        $updatedLast24h = 0;

        $dayAgo = $now->subDay();

        foreach ($covered as $id => $security) {

            $counts[FundDataCoverage::status(true, $latestDates[$id] ?? null)]++;

            $lastSuccess = $attempts[$id]['last_success_at'] ?? null;

            if ($lastSuccess && CarbonImmutable::parse($lastSuccess)->gte($dayAgo)) {

                $updatedLast24h++;
            }
        }

        return [
            'covered' => $covered->count(),
            'current' => $counts[FundDataCoverage::CURRENT],
            'stale' => $counts[FundDataCoverage::STALE],
            'pending' => $counts[FundDataCoverage::PENDING],
            'updated_last_24h' => $updatedLast24h,
        ];
    }

    private function issuers($covered, array $latestDates, array $attempts, CarbonImmutable $now): array
    {
        $since = $now->subDays(self::ISSUER_WINDOW_DAYS);

        $byIssuer = $covered->groupBy(fn (Security $security) => $security->detail?->etf_issuer_id);

        $stats = DB::table('security_ingestion_batch_items AS i')
            ->join('security_details AS d', 'd.security_id', '=', 'i.security_id')
            ->where('i.security_update_type_id', SecurityUpdateType::FUND_DATA)
            ->where('i.completed_at', '>=', $since)
            ->whereIn('i.security_id', $covered->keys()->all() ?: [0])
            ->groupBy('d.etf_issuer_id')
            ->selectRaw('d.etf_issuer_id AS issuer_id')
            ->selectRaw('SUM(CASE WHEN i.is_success = 1 THEN 1 ELSE 0 END) AS successes')
            ->selectRaw('SUM(CASE WHEN i.status_id = ? THEN 1 ELSE 0 END) AS failures', [Status::FAILED])
            ->selectRaw('MAX(CASE WHEN i.is_success = 1 THEN i.completed_at END) AS last_success_at')
            ->get()
            ->keyBy('issuer_id');

        $names = EtfIssuer::whereIn('id', $byIssuer->keys()->filter()->all())
            ->pluck('etf_issuer_name', 'id');

        $rows = [];

        foreach ($byIssuer as $issuerId => $securities) {

            $stat = $stats->get($issuerId);

            $successes = (int) ($stat->successes ?? 0);

            $failures = (int) ($stat->failures ?? 0);

            $attemptCount = $successes + $failures;

            $statuses = $securities->map(
                fn (Security $security) => FundDataCoverage::status(true, $latestDates[$security->id] ?? null)
            );

            $rows[] = [
                'issuer_id' => $issuerId,
                'issuer_name' => $names[$issuerId] ?? 'Unknown issuer',
                'covered' => $securities->count(),
                'attempts' => $attemptCount,
                'successes' => $successes,
                'failures' => $failures,
                'success_rate' => $attemptCount > 0 ? round($successes / $attemptCount * 100, 1) : null,
                'last_success_at' => $stat->last_success_at ?? null,
                'failing_now' => $securities->filter(
                    fn (Security $security) => FundDataCoverage::latestAttemptFailed($attempts[$security->id] ?? [])
                )->count(),
                'stale' => $statuses->filter(fn ($s) => $s === FundDataCoverage::STALE)->count(),
                'pending' => $statuses->filter(fn ($s) => $s === FundDataCoverage::PENDING)->count(),
            ];
        }

        // Worst first: share of ETFs failing now, then success rate, then stale.
        usort($rows, fn ($a, $b) => [-$a['failing_now'] / max(1, $a['covered']), ($a['success_rate'] ?? 101), -$a['stale']]
            <=> [-$b['failing_now'] / max(1, $b['covered']), ($b['success_rate'] ?? 101), -$b['stale']]);

        return $rows;
    }

    private function problems($covered, array $latestDates, array $attempts, $unscheduled): array
    {
        $rows = [];

        foreach ($unscheduled as $security) {

            $rows[] = [
                'security_id' => $security->id,
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
                'status' => 'unscheduled',
                'latest_aum_date' => $latestDates[$security->id] ?? null,
                'latest_attempt_failed' => false,
                'last_success_at' => null,
                'last_failure_at' => null,
                'last_error' => null,
            ];
        }

        foreach ($covered as $id => $security) {

            $latestDate = $latestDates[$id] ?? null;

            $status = FundDataCoverage::status(true, $latestDate);

            $attempt = $attempts[$id] ?? [];

            $lastFailure = $attempt['last_failure_at'] ?? null;

            $lastSuccess = $attempt['last_success_at'] ?? null;

            $latestAttemptFailed = FundDataCoverage::latestAttemptFailed($attempt);

            if ($status === FundDataCoverage::CURRENT && ! $latestAttemptFailed) {

                continue;
            }

            $rows[] = [
                'security_id' => $id,
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
                'status' => $status,
                'latest_aum_date' => $latestDate,
                'latest_attempt_failed' => $latestAttemptFailed,
                'last_success_at' => $lastSuccess,
                'last_failure_at' => $lastFailure,
                'last_error' => $latestAttemptFailed ? $attempt['last_error'] : null,
            ];
        }

        $order = [
            FundDataCoverage::STALE => 0,
            'unscheduled' => 1,
            FundDataCoverage::PENDING => 2,
            FundDataCoverage::CURRENT => 3,
        ];

        usort($rows, fn ($a, $b) => [$order[$a['status']], $a['latest_aum_date'] ?? '', $a['symbol']]
            <=> [$order[$b['status']], $b['latest_aum_date'] ?? '', $b['symbol']]);

        return $rows;
    }

    private function crons(CarbonImmutable $now): array
    {
        $dayAgo = $now->subDay();

        // cron_name stores the full command signature; the command is the first token.
        $logs = CronLog::query()
            ->where('start_time', '>=', $now->subDays(8))
            ->orderByDesc('start_time')
            ->get(['cron_name', 'status_id', 'run_time', 'start_time', 'cron_fail_details'])
            ->groupBy(fn (CronLog $log) => strtok(trim($log->cron_name), " \n"));

        $rows = [];

        foreach (self::EXPECTED_CRONS as $command => $config) {

            $runs = $logs->get($command, collect());

            $last = $runs->first();

            $lastRunAt = $last ? CarbonImmutable::parse($last->start_time) : null;

            $recent = $runs->filter(
                fn (CronLog $log) => CarbonImmutable::parse($log->start_time)->gte($dayAgo)
            );

            $rows[] = [
                'command' => $command,
                'label' => $config['label'],
                'schedule' => $config['schedule'],
                'last_run_at' => $lastRunAt?->toIso8601String(),
                'last_status' => $last
                    ? ((int) $last->status_id === Status::FAILED ? 'failed' : 'completed')
                    : null,
                'last_run_time_seconds' => $last?->run_time,
                'last_fail_details' => $last && (int) $last->status_id === Status::FAILED
                    ? $last->cron_fail_details
                    : null,
                'runs_last_24h' => $recent->count(),
                'failures_last_24h' => $recent->filter(
                    fn (CronLog $log) => (int) $log->status_id === Status::FAILED
                )->count(),
                'is_overdue' => ! $lastRunAt
                    || $lastRunAt->lt($now->subHours($config['max_gap_hours'])),
            ];
        }

        return $rows;
    }

    /**
     * critical: the scraper cron is overdue or failing, or scraping mostly fails.
     * warning:  anything else needs attention (stale ETFs, other crons, a weak issuer).
     */
    private function overall(array $coverage, array $issuers, array $crons): array
    {
        $reasons = [];

        $critical = false;

        foreach ($crons as $cron) {

            if ($cron['is_overdue']) {

                $reasons[] = "{$cron['label']} has not run on schedule.";

                $critical = $critical || $cron['command'] === 'securities:run-scheduled-updates';
            } elseif ($cron['last_status'] === 'failed') {

                $reasons[] = "{$cron['label']} failed on its last run.";

                $critical = $critical || $cron['command'] === 'securities:run-scheduled-updates';
            }
        }

        $attempts = array_sum(array_column($issuers, 'attempts'));

        $successes = array_sum(array_column($issuers, 'successes'));

        if ($attempts > 0 && $successes / $attempts < 0.5) {

            $reasons[] = 'Less than half of scrape attempts succeeded in the last '.self::ISSUER_WINDOW_DAYS.' days.';

            $critical = true;
        }

        foreach ($issuers as $issuer) {

            if ($issuer['covered'] > 0 && $issuer['failing_now'] === $issuer['covered']) {

                $reasons[] = "Every {$issuer['issuer_name']} ETF failed its latest scrape; the scraper may be broken.";

                $critical = true;

                continue;
            }

            if ($issuer['covered'] > 0 && $issuer['failing_now'] / $issuer['covered'] >= 0.25) {

                $reasons[] = "{$issuer['failing_now']} of {$issuer['covered']} {$issuer['issuer_name']} ETFs failed their latest scrape.";

                continue;
            }

            if ($issuer['success_rate'] !== null && $issuer['success_rate'] < 80) {

                $reasons[] = "{$issuer['issuer_name']} scrapes are succeeding only {$issuer['success_rate']}% of the time.";
            }
        }

        if ($coverage['stale'] > 0) {

            $reasons[] = "{$coverage['stale']} ETF(s) have stale data.";
        }

        if (($coverage['unscheduled'] ?? 0) > 0) {

            $reasons[] = "{$coverage['unscheduled']} ETF(s) have a scraper but no update schedule, so they are never scraped. Run an AUM update to schedule and scrape them.";
        }

        return [
            'status' => $critical ? 'critical' : (empty($reasons) ? 'healthy' : 'warning'),
            'reasons' => $reasons,
        ];
    }
}
