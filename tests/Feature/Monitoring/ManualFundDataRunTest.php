<?php

namespace Tests\Feature\Monitoring;

use App\Jobs\FinalizeScheduledUpdatesBatchJob;
use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\EtfIssuerSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManualFundDataRunTest extends TestCase
{
    private const TABLES = [
        'securities',
        'security_details',
        'security_update_schedules',
        'security_aum_histories',
        'security_ingestion_batches',
        'security_ingestion_batch_items',
        'cron_logs',
        'import_logs',
        'personal_access_tokens',
        'users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-08 12:00:00');

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        DB::table('etf_issuers')->truncate();
        $this->seed(EtfIssuerSeeder::class);

        Queue::fake();
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

    private function security(string $symbol, int $issuerId = EtfIssuer::YIELDMAX, bool $scheduled = true): Security
    {
        $security = Security::factory()->create(['symbol' => $symbol]);

        SecurityDetail::where('security_id', $security->id)->update(['etf_issuer_id' => $issuerId]);

        if ($scheduled) {
            SecurityUpdateSchedule::factory()->aum()->scheduledFor(1, 3)->create(['security_id' => $security->id]);
        }

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

    private function failedAttempt(Security $security): void
    {
        DB::table('security_ingestion_batch_items')->insert([
            'security_ingestion_batch_id' => 999,
            'security_id' => $security->id,
            'status_id' => Status::FAILED,
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
            'is_processed' => true,
            'is_success' => false,
            'error_message' => 'selector not found',
            'completed_at' => now()->subHour(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
    }

    /**
     * GOOD current; OLDD stale; SOON awaiting data; BRKN failing (Kurv);
     * NEWW has a scraper but no schedule; DEFI has no scraper.
     */
    private function portfolio(): array
    {
        $good = $this->security('GOOD');
        $this->aum($good, '2026-11-06');

        $stale = $this->security('OLDD');
        $this->aum($stale, '2026-10-01');

        $pending = $this->security('SOON');

        $broken = $this->security('BRKN', EtfIssuer::KURV);
        $this->aum($broken, '2026-11-06');
        $this->failedAttempt($broken);

        $unscheduled = $this->security('NEWW', scheduled: false);

        $noScraper = $this->security('DEFI', EtfIssuer::DEFIANCE);

        return compact('good', 'stale', 'pending', 'broken', 'unscheduled', 'noScraper');
    }

    private function queuedSecurityIds(): array
    {
        $scheduleIds = [];

        Queue::assertPushed(RunScheduledSecurityUpdateJob::class, function ($job) use (&$scheduleIds) {
            $scheduleIds[] = $job->scheduleId;

            return true;
        });

        return SecurityUpdateSchedule::whereIn('id', $scheduleIds)->pluck('security_id')->sort()->values()->all();
    }

    public function test_problems_scope_queues_needing_attention_and_schedules_never_scheduled_etfs(): void
    {
        $this->actingAsAdmin();

        $etfs = $this->portfolio();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'problems'])
            ->assertCreated()
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.processed', 0)
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonPath('data.schedules_created', 1);

        $expected = collect(['stale', 'pending', 'broken', 'unscheduled'])
            ->map(fn ($key) => $etfs[$key]->id)->sort()->values()->all();

        $this->assertSame($expected, $this->queuedSecurityIds());

        // NEWW now has an active FUND_DATA schedule for the hourly run.
        $this->assertDatabaseHas('security_update_schedules', [
            'security_id' => $etfs['unscheduled']->id,
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
            'status_id' => Status::ACTIVE,
        ]);

        $this->assertStringContainsString(
            'Manual fund data run (ETFs needing attention)',
            SecurityIngestionBatch::latest('id')->value('processing_notes')
        );
    }

    public function test_all_and_issuer_scopes(): void
    {
        $this->actingAsAdmin();

        $etfs = $this->portfolio();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'issuer', 'issuer_id' => EtfIssuer::KURV])
            ->assertCreated()
            ->assertJsonPath('data.total', 1);

        $this->assertSame([$etfs['broken']->id], $this->queuedSecurityIds());

        // Finish that run so another can start.
        SecurityIngestionBatch::query()->update(['processed_count' => DB::raw('total_securities')]);

        Queue::fake();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])
            ->assertCreated()
            ->assertJsonPath('data.total', 5);

        $this->assertNotContains($etfs['noScraper']->id, $this->queuedSecurityIds());
    }

    public function test_only_one_manual_run_at_a_time_unless_the_previous_run_stalled(): void
    {
        $this->actingAsAdmin();

        $this->portfolio();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertCreated();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        // A run unfinished after an hour (e.g. no queue worker) stops blocking.
        Carbon::setTestNow(now()->addMinutes(61));

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertCreated();
    }

    public function test_progress_endpoints_report_counts_and_failures(): void
    {
        $this->actingAsAdmin();

        $etfs = $this->portfolio();

        $runId = $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'issuer', 'issuer_id' => EtfIssuer::YIELDMAX])
            ->assertCreated()
            ->json('data.id');

        // Simulate the queued jobs: one success, one failure.
        $items = DB::table('security_ingestion_batch_items')->where('security_ingestion_batch_id', $runId)->orderBy('id')->get();

        DB::table('security_ingestion_batch_items')->where('id', $items[0]->id)->update(['status_id' => Status::COMPLETED, 'is_success' => true]);
        DB::table('security_ingestion_batch_items')->where('id', $items[1]->id)->update(['status_id' => Status::FAILED, 'error_message' => 'timeout']);
        SecurityIngestionBatch::whereKey($runId)->update(['processed_count' => 2, 'success_count' => 1, 'failure_count' => 1]);

        $this->getJson("/api/admin/pipeline-health/runs/{$runId}")
            ->assertOk()
            ->assertJsonPath('data.processed', 2)
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonCount(1, 'data.failures')
            ->assertJsonPath('data.failures.0.error', 'timeout');

        $this->getJson('/api/admin/pipeline-health/runs/latest')
            ->assertOk()
            ->assertJsonPath('data.id', $runId);

        $this->getJson('/api/admin/pipeline-health/runs/999999')->assertNotFound();

        $this->assertNotNull($etfs['good']);
    }

    public function test_new_schedules_use_the_least_busy_hour(): void
    {
        $this->actingAsAdmin();

        foreach (range(0, 23) as $hour) {
            if ($hour === 7) {
                continue;
            }

            $security = $this->security("H{$hour}", scheduled: false);

            SecurityUpdateSchedule::factory()->aum()->scheduledFor(1, $hour)->create(['security_id' => $security->id]);
        }

        $new = $this->security('NEWW', scheduled: false);

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertCreated();

        $this->assertSame(
            7,
            (int) SecurityUpdateSchedule::where('security_id', $new->id)->value('run_hour')
        );
    }

    public function test_pipeline_health_reports_never_scheduled_etfs(): void
    {
        $this->actingAsAdmin();

        $this->portfolio();

        $response = $this->getJson('/api/admin/pipeline-health')->assertOk();

        $response->assertJsonPath('data.coverage.unscheduled', 1);

        $this->assertContains(
            'NEWW',
            collect($response->json('data.problems'))->where('status', 'unscheduled')->pluck('symbol')->all()
        );
    }

    public function test_validation_and_admin_only(): void
    {
        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['is_admin' => false]), ['*']);

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertStatus(401);

        $this->actingAsAdmin();

        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'issuer'])->assertStatus(422);
        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'everything'])->assertStatus(422);

        // Nothing covered yet.
        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertStatus(422);
    }

    public function test_finished_runs_stay_findable_after_finalization(): void
    {
        $this->actingAsAdmin();

        $this->portfolio();

        $runId = $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Manual run: all covered ETFs')
            ->json('data.id');

        SecurityIngestionBatch::whereKey($runId)->update([
            'processed_count' => DB::raw('total_securities'),
            'success_count' => DB::raw('total_securities'),
        ]);

        // The finalize job rewrites the notes; the manual prefix must survive.
        FinalizeScheduledUpdatesBatchJob::dispatchSync($runId);

        $this->assertStringStartsWith(
            'Manual fund data run (all covered ETFs)',
            SecurityIngestionBatch::find($runId)->processing_notes
        );

        $this->getJson('/api/admin/pipeline-health/runs/latest')
            ->assertOk()
            ->assertJsonPath('data.id', $runId)
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.description', 'Manual run: all covered ETFs');

        // A finished run no longer blocks a new one.
        $this->postJson('/api/admin/pipeline-health/runs', ['scope' => 'all'])->assertCreated();
    }
}
