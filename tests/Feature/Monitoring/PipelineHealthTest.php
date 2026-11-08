<?php

namespace Tests\Feature\Monitoring;

use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Models\User;
use App\Queries\Admin\Monitoring\PipelineHealthQuery;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineHealthTest extends TestCase
{
    private const TABLES = [
        'security_aum_histories',
        'securities',
        'security_details',
        'security_update_schedules',
        'security_ingestion_batch_items',
        'cron_logs',
        'personal_access_tokens',
        'users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-11 12:00:00');

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        Carbon::setTestNow();

        parent::tearDown();
    }

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]), ['*']);
    }

    private function coveredSecurity(string $symbol, int $issuerId = EtfIssuer::YIELDMAX): Security
    {
        $security = Security::factory()->create(['symbol' => $symbol]);

        SecurityDetail::where('security_id', $security->id)
            ->update(['etf_issuer_id' => $issuerId]);

        SecurityUpdateSchedule::factory()->aum()->create([
            'security_id' => $security->id,
        ]);

        return $security;
    }

    private function aum(Security $security, string $date): void
    {
        SecurityAumHistory::create([
            'security_id' => $security->id,
            'aum_date' => $date,
            'assets_under_management' => 1_000_000,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);
    }

    private function attempt(Security $security, string $at, ?string $error = null): void
    {
        DB::table('security_ingestion_batch_items')->insert([
            'security_ingestion_batch_id' => 1,
            'security_id' => $security->id,
            'status_id' => $error ? Status::FAILED : Status::COMPLETED,
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
            'attempts' => 1,
            'is_processed' => true,
            'is_success' => $error === null,
            'error_message' => $error,
            'started_at' => $at,
            'completed_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function cronRun(string $command, string $at, int $statusId = Status::COMPLETED): void
    {
        DB::table('cron_logs')->insert([
            // Real rows store the full signature, options included.
            'cron_name' => "{$command}\n        {--symbol= : option}",
            'status_id' => $statusId,
            'cron_description' => 'test',
            'cron_fail_details' => $statusId === Status::FAILED ? 'boom' : null,
            'interval_id' => 1,
            'run_time' => 3,
            'start_time' => $at,
            'end_time' => $at,
            'notification_status_id' => 3,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    public function test_it_requires_an_admin(): void
    {
        $this->getJson('/api/admin/pipeline-health')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['is_admin' => false]), ['*']);

        $this->getJson('/api/admin/pipeline-health')->assertStatus(401);
    }

    public function test_it_reports_coverage_issuer_health_and_problems(): void
    {
        $this->actingAsAdmin();

        // YieldMax: one healthy, one stale.
        $healthy = $this->coveredSecurity('GOOD');
        $this->aum($healthy, '2026-05-08');
        $this->attempt($healthy, '2026-05-11 03:00:00');

        $stale = $this->coveredSecurity('OLDD');
        $this->aum($stale, '2026-04-20');
        $this->attempt($stale, '2026-05-11 04:00:00');

        // Kurv: every ETF failing on its latest attempt.
        $broken = $this->coveredSecurity('BRKN', EtfIssuer::KURV);
        $this->aum($broken, '2026-05-08');
        $this->attempt($broken, '2026-05-09 05:00:00');
        $this->attempt($broken, '2026-05-11 05:00:00', 'Unable to locate AUM value');

        // Covered but no data yet.
        $this->coveredSecurity('SOON');

        $response = $this->getJson('/api/admin/pipeline-health')->assertOk();

        $response
            ->assertJsonPath('data.coverage.covered', 4)
            ->assertJsonPath('data.coverage.current', 2)
            ->assertJsonPath('data.coverage.stale', 1)
            ->assertJsonPath('data.coverage.pending', 1)
            ->assertJsonPath('data.coverage.updated_last_24h', 2)
            ->assertJsonPath('data.overall.status', 'critical');

        // Worst issuer first.
        $response
            ->assertJsonPath('data.issuers.0.issuer_id', EtfIssuer::KURV)
            ->assertJsonPath('data.issuers.0.failing_now', 1)
            ->assertJsonPath('data.issuers.0.success_rate', 50);

        $problems = collect($response->json('data.problems'))->keyBy('symbol');

        $this->assertEqualsCanonicalizing(['OLDD', 'SOON', 'BRKN'], $problems->keys()->all());
        $this->assertSame('stale', $problems['OLDD']['status']);
        $this->assertSame('pending', $problems['SOON']['status']);
        $this->assertTrue($problems['BRKN']['latest_attempt_failed']);
        $this->assertSame('Unable to locate AUM value', $problems['BRKN']['last_error']);

        // Stale ETFs sort first.
        $this->assertSame('OLDD', $response->json('data.problems.0.symbol'));
    }

    public function test_it_reports_last_cron_runs_and_overdue_crons(): void
    {
        $this->actingAsAdmin();

        $this->cronRun('securities:run-scheduled-updates', '2026-05-11 11:00:00');
        $this->cronRun('securities:run-scheduled-updates', '2026-05-11 10:00:00', Status::FAILED);
        $this->cronRun('app:cleanup-ai-pipeline-data', '2026-05-09 02:00:00');

        $crons = collect(
            $this->getJson('/api/admin/pipeline-health')->assertOk()->json('data.crons')
        )->keyBy('command');

        $scraper = $crons['securities:run-scheduled-updates'];

        $this->assertSame('completed', $scraper['last_status']);
        $this->assertFalse($scraper['is_overdue']);
        $this->assertSame(2, $scraper['runs_last_24h']);
        $this->assertSame(1, $scraper['failures_last_24h']);

        // Daily cron last ran 58 hours ago.
        $this->assertTrue($crons['app:cleanup-ai-pipeline-data']['is_overdue']);

        // Never ran.
        $this->assertNull($crons['app:trim-cron-logs']['last_run_at']);
        $this->assertTrue($crons['app:trim-cron-logs']['is_overdue']);
    }

    public function test_expected_crons_match_the_schedule(): void
    {
        $scheduled = collect(app(Schedule::class)->events())
            ->map(fn ($event) => preg_match("/artisan'? ([\\w:-]+)/", $event->command, $m) ? $m[1] : null)
            ->filter()
            ->sort()
            ->values()
            ->all();

        $expected = collect(array_keys(PipelineHealthQuery::EXPECTED_CRONS))->sort()->values()->all();

        $this->assertNotEmpty($scheduled, 'No scheduled events were loaded.');
        $this->assertSame($expected, $scheduled);
    }
}
