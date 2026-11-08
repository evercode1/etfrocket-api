<?php

namespace Tests\Feature\Console;

use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\IntervalSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeedPipelineActivityCommandTest extends TestCase
{
    private const TABLES = [
        'securities',
        'security_details',
        'security_update_schedules',
        'security_ingestion_batches',
        'security_ingestion_batch_items',
        'cron_logs',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        // Lookup tables are reseeded (not just truncated) and left in place
        // afterwards, since other tests may rely on them.
        DB::table('intervals')->truncate();
        DB::table('etf_issuers')->truncate();

        $this->seed([IntervalSeeder::class, EtfIssuerSeeder::class]);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        parent::tearDown();
    }

    public function test_it_generates_cron_and_scrape_history_with_a_failing_issuer(): void
    {
        foreach (['GOOD' => EtfIssuer::YIELDMAX, 'BRKN' => EtfIssuer::KURV] as $symbol => $issuer) {
            $security = Security::factory()->create(['symbol' => $symbol]);

            SecurityDetail::where('security_id', $security->id)->update(['etf_issuer_id' => $issuer]);

            SecurityUpdateSchedule::factory()->aum()->create(['security_id' => $security->id]);
        }

        $this->artisan('dev:seed-pipeline-activity', [
            '--days' => 3,
            '--failing-issuer' => 'Kurv',
            '--failing-days' => 3,
        ])->assertSuccessful();

        $this->assertGreaterThan(0, DB::table('cron_logs')->count());
        $this->assertGreaterThan(0, DB::table('security_ingestion_batches')->count());

        $kurvId = Security::where('symbol', 'BRKN')->value('id');

        $this->assertSame(
            0,
            DB::table('security_ingestion_batch_items')
                ->where('security_id', $kurvId)
                ->where('is_success', true)
                ->count()
        );
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('dev:seed-pipeline-activity')->assertFailed();

        $this->assertSame(0, DB::table('cron_logs')->count());
    }
}
