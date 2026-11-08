<?php

namespace App\Console\Commands;

use App\Models\EtfIssuer;
use App\Models\ImportType;
use App\Models\Interval;
use App\Models\NotificationStatus;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Queries\Admin\Monitoring\PipelineHealthQuery;
use App\Services\Aum\FundDataCoverage;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Synthetic pipeline activity for local development
|--------------------------------------------------------------------------
|
| Fills cron_logs, security_ingestion_batches and
| security_ingestion_batch_items with a realistic recent history so the
| admin Pipeline Health page can be built and checked locally:
|
| - every expected cron runs on its schedule (all succeed);
| - each covered ETF is scraped once a day; ~2% of attempts time out;
| - --failing-issuer makes one issuer fail every attempt for the last
|   --failing-days days (simulates a scraper broken by a site redesign).
|
| Refuses to run in production. --fresh truncates those three tables first.
|
*/

class SeedPipelineActivity extends Command
{
    protected $signature =

        'dev:seed-pipeline-activity

        {--days=7 : Days of history to generate, ending now}

        {--failing-issuer=Kurv : Issuer name (partial match) whose scraper is "broken"; empty for none}

        {--failing-days=3 : How many recent days the failing issuer has been failing}

        {--fresh : Truncate cron_logs and the ingestion batch tables first}';

    protected $description =

        'Generate synthetic cron and scrape history for the admin Pipeline Health page (refuses to run in production).';

    public function handle(): int
    {
        if (app()->environment('production')) {

            $this->error('dev:seed-pipeline-activity cannot run in production.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));

        $failingDays = max(0, (int) $this->option('failing-days'));

        $failingIssuerId = $this->failingIssuerId();

        if ($this->option('fresh')) {

            DB::table('security_ingestion_batch_items')->truncate();
            DB::table('security_ingestion_batches')->truncate();
            DB::table('cron_logs')->truncate();

            $this->info('Truncated cron_logs, security_ingestion_batches and security_ingestion_batch_items.');
        }

        $now = CarbonImmutable::now()->startOfHour();

        $start = $now->subDays($days);

        $cronCount = $this->seedCrons($start, $now);

        [$batchCount, $itemCount, $failureCount] = $this->seedScrapes(
            $start,
            $now,
            $failingIssuerId,
            $now->subDays($failingDays)
        );

        $this->info(
            "Seeded {$cronCount} cron runs, {$batchCount} scrape batches, ".
            "{$itemCount} scrape attempts ({$failureCount} failed)."
        );

        return self::SUCCESS;
    }

    private function failingIssuerId(): ?int
    {
        $name = trim((string) $this->option('failing-issuer'));

        if ($name === '') {

            return null;
        }

        $id = EtfIssuer::where('etf_issuer_name', 'like', "%{$name}%")->value('id');

        if (! $id) {

            $this->warn("No issuer matches \"{$name}\"; no issuer will be failing.");
        }

        return $id;
    }

    private function seedCrons(CarbonImmutable $start, CarbonImmutable $now): int
    {
        $rows = [];

        $intervalIds = [
            'hourly' => Interval::getIntervalId('Hourly'),
            'daily' => Interval::getIntervalId('Daily'),
            'weekly' => Interval::getIntervalId('Weekly'),
        ];

        foreach (PipelineHealthQuery::EXPECTED_CRONS as $command => $config) {

            $runTimes = match (true) {
                $config['max_gap_hours'] <= 2 => $this->every($start, $now, 'PT1H'),
                $config['max_gap_hours'] <= 26 => $this->every($start->setTime(2, 0), $now, 'P1D'),
                default => $this->every($start->next(CarbonImmutable::SUNDAY)->setTime(4, 0), $now, 'P7D'),
            };

            $interval = match (true) {
                $config['max_gap_hours'] <= 2 => $intervalIds['hourly'],
                $config['max_gap_hours'] <= 26 => $intervalIds['daily'],
                default => $intervalIds['weekly'],
            };

            foreach ($runTimes as $time) {

                $runTime = random_int(1, 20);

                $rows[] = [
                    'cron_name' => $command,
                    'status_id' => Status::COMPLETED,
                    'cron_description' => $config['label'].' (synthetic)',
                    'cron_fail_details' => null,
                    'interval_id' => $interval,
                    'run_time' => $runTime,
                    'start_time' => $time,
                    'end_time' => $time->addSeconds($runTime),
                    'notification_status_id' => NotificationStatus::NOTHING_TO_SEND,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {

            DB::table('cron_logs')->insert($chunk);
        }

        return count($rows);
    }

    /**
     * @return CarbonImmutable[]
     */
    private function every(CarbonImmutable $from, CarbonImmutable $to, string $interval): array
    {
        $times = [];

        for ($time = $from; $time->lte($to); $time = $time->add(new \DateInterval($interval))) {

            $times[] = $time;
        }

        return $times;
    }

    /**
     * One batch per day; each covered ETF is attempted once per day at its
     * schedule's run_hour.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function seedScrapes(
        CarbonImmutable $start,
        CarbonImmutable $now,
        ?int $failingIssuerId,
        CarbonImmutable $failingSince
    ): array {

        $securities = FundDataCoverage::coveredSecurities()
            ->with('detail')
            ->get();

        $runHours = DB::table('security_update_schedules')
            ->where('security_update_type_id', SecurityUpdateType::FUND_DATA)
            ->where('status_id', Status::ACTIVE)
            ->pluck('run_hour', 'security_id');

        $batches = 0;

        $items = 0;

        $failures = 0;

        for ($day = $start->startOfDay(); $day->lte($now); $day = $day->addDay()) {

            $attempts = [];

            foreach ($securities as $security) {

                $attemptAt = $day->setTime((int) ($runHours[$security->id] ?? 0), random_int(0, 59));

                if ($attemptAt->lt($start) || $attemptAt->gt($now)) {

                    continue;
                }

                $broken = $failingIssuerId
                    && $security->detail?->etf_issuer_id === $failingIssuerId
                    && $attemptAt->gte($failingSince);

                $error = match (true) {
                    $broken => 'Unable to locate AUM value on issuer page (selector not found).',
                    random_int(1, 100) <= 2 => 'cURL error 28: Operation timed out after 60000 milliseconds.',
                    default => null,
                };

                $attempts[] = [$security, $attemptAt, $error];
            }

            if (empty($attempts)) {

                continue;
            }

            $failed = count(array_filter($attempts, fn ($a) => $a[2] !== null));

            $batchStart = collect($attempts)->min(fn ($a) => $a[1]);

            $batchEnd = collect($attempts)->max(fn ($a) => $a[1])->addMinutes(2);

            $batchId = DB::table('security_ingestion_batches')->insertGetId([
                'batch_uuid' => (string) Str::uuid(),
                'import_type_id' => ImportType::SCHEDULED_SECURITY_UPDATES,
                'status_id' => $failed ? Status::FAILED : Status::COMPLETED,
                'total_securities' => count($attempts),
                'processed_count' => count($attempts),
                'success_count' => count($attempts) - $failed,
                'failure_count' => $failed,
                'duplicate_count' => 0,
                'passed_data_integrity_check' => $failed === 0,
                'processing_notes' => 'Synthetic scheduled fund data updates.',
                'import_fail_details' => null,
                'started_at' => $batchStart,
                'completed_at' => $batchEnd,
                'created_at' => $batchStart,
                'updated_at' => $batchEnd,
            ]);

            $rows = [];

            foreach ($attempts as [$security, $attemptAt, $error]) {

                $completedAt = $attemptAt->addSeconds(random_int(2, 40));

                $rows[] = [
                    'security_ingestion_batch_id' => $batchId,
                    'security_id' => $security->id,
                    'status_id' => $error ? Status::FAILED : Status::COMPLETED,
                    'security_update_schedule_id' => null,
                    'security_update_type_id' => SecurityUpdateType::FUND_DATA,
                    'attempts' => $error ? 3 : 1,
                    'runtime_ms' => random_int(800, 9000),
                    'is_processed' => true,
                    'is_success' => $error === null,
                    'error_message' => $error,
                    'started_at' => $attemptAt,
                    'completed_at' => $completedAt,
                    'created_at' => $attemptAt,
                    'updated_at' => $completedAt,
                ];
            }

            DB::table('security_ingestion_batch_items')->insert($rows);

            $batches++;

            $items += count($rows);

            $failures += $failed;
        }

        return [$batches, $items, $failures];
    }
}
