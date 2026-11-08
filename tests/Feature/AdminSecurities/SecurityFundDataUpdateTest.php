<?php

namespace Tests\Feature\AdminSecurities;

use App\Http\Controllers\Public\Aum\AumLeaderboardController;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Models\User;
use App\Services\Scrapers\YieldMaxScraperService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityFundDataUpdateTest extends TestCase
{
    private const TABLES = [
        'securities',
        'security_details',
        'security_update_schedules',
        'security_aum_histories',
        'security_nav_histories',
        'security_ingestion_batches',
        'security_ingestion_batch_items',
        'ai_data_extractions',
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

    private function security(string $symbol, ?int $issuerId = EtfIssuer::YIELDMAX, bool $scheduled = true): Security
    {
        $security = Security::factory()->create(['symbol' => $symbol]);

        SecurityDetail::where('security_id', $security->id)->update(['etf_issuer_id' => $issuerId]);

        if ($scheduled) {
            SecurityUpdateSchedule::factory()->aum()->create(['security_id' => $security->id]);
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

    /*
    |--------------------------------------------------------------------------
    | List: coverage fields and filter
    |--------------------------------------------------------------------------
    */

    public function test_list_includes_coverage_fields_and_filters_by_coverage(): void
    {
        $this->actingAsAdmin();

        $current = $this->security('CURR');
        $this->aum($current, '2026-05-08');

        $stale = $this->security('STAL');
        $this->aum($stale, '2026-04-20');

        $this->security('PEND');

        $this->security('NOCV', EtfIssuer::DEFIANCE, scheduled: false);

        $rows = collect(
            $this->getJson('/api/admin/list-securities-data?per_page=50')->assertOk()->json('data.data')
        )->keyBy('symbol');

        $this->assertSame('current', $rows['CURR']['coverage_status']);
        $this->assertSame('2026-05-08', $rows['CURR']['latest_aum_date']);
        $this->assertTrue($rows['CURR']['has_scraper']);
        $this->assertFalse($rows['CURR']['last_scrape']['failed']);

        $this->assertSame('stale', $rows['STAL']['coverage_status']);
        $this->assertSame('pending', $rows['PEND']['coverage_status']);
        $this->assertSame('not_covered', $rows['NOCV']['coverage_status']);
        $this->assertFalse($rows['NOCV']['has_scraper']);

        $this->getJson('/api/admin/list-securities-data?coverage=stale')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.symbol', 'STAL');

        $this->getJson('/api/admin/list-securities-data?coverage=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.symbol', 'PEND');
    }

    public function test_list_can_filter_to_securities_whose_latest_scrape_failed(): void
    {
        $this->actingAsAdmin();

        $failing = $this->security('FAIL');
        $this->aum($failing, '2026-05-08');

        $healthy = $this->security('GOOD');
        $this->aum($healthy, '2026-05-08');

        foreach ([[$failing, false, 'selector not found'], [$healthy, true, null]] as [$security, $ok, $error]) {
            DB::table('security_ingestion_batch_items')->insert([
                'security_ingestion_batch_id' => 1,
                'security_id' => $security->id,
                'status_id' => $ok ? Status::COMPLETED : Status::FAILED,
                'security_update_type_id' => SecurityUpdateType::FUND_DATA,
                'is_processed' => true,
                'is_success' => $ok,
                'error_message' => $error,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->getJson('/api/admin/list-securities-data?coverage=failing')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.symbol', 'FAIL')
            ->assertJsonPath('data.data.0.last_scrape.failed', true)
            ->assertJsonPath('data.data.0.last_scrape.last_error', 'selector not found');
    }

    /*
    |--------------------------------------------------------------------------
    | Update now
    |--------------------------------------------------------------------------
    */

    public function test_update_now_scrapes_saves_data_and_records_a_successful_attempt(): void
    {
        $this->actingAsAdmin();

        $security = $this->security('ULTY');

        $this->mock(YieldMaxScraperService::class, function ($mock) {
            $mock->shouldReceive('extract')->once()->andReturn([
                'symbol' => 'ULTY',
                'assets_under_management' => 250_000_000,
                'aum_date' => '2026-05-08',
                'nav_per_share' => 14.25,
                'nav_date' => '2026-05-08',
                'shares_outstanding' => 17_543_860,
            ]);
        });

        Cache::put(AumLeaderboardController::CACHE_KEY, ['stale' => true], 600);

        $this->postJson("/api/admin/security-data-run-update/{$security->id}")
            ->assertOk()
            ->assertJsonPath('data.succeeded', true)
            ->assertJsonPath('data.data.assets_under_management', 250_000_000)
            ->assertJsonPath('data.error', null);

        $this->assertDatabaseHas('security_aum_histories', [
            'security_id' => $security->id,
            'aum_date' => '2026-05-08',
            'assets_under_management' => 250_000_000,
        ]);

        $this->assertDatabaseHas('security_ingestion_batch_items', [
            'security_id' => $security->id,
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
            'is_success' => true,
            'status_id' => Status::COMPLETED,
        ]);

        // Public leaderboard cache cleared so the new value shows immediately.
        $this->assertFalse(Cache::has(AumLeaderboardController::CACHE_KEY));
    }

    public function test_update_now_reports_scrape_failures_without_erroring(): void
    {
        $this->actingAsAdmin();

        $security = $this->security('ULTY');

        $this->mock(YieldMaxScraperService::class, function ($mock) {
            $mock->shouldReceive('extract')->once()
                ->andThrow(new \RuntimeException('Unable to locate AUM value on issuer page.'));
        });

        $this->postJson("/api/admin/security-data-run-update/{$security->id}")
            ->assertOk()
            ->assertJsonPath('data.succeeded', false)
            ->assertJsonPath('data.error', 'Unable to locate AUM value on issuer page.');

        $this->assertDatabaseHas('security_ingestion_batch_items', [
            'security_id' => $security->id,
            'is_success' => false,
            'status_id' => Status::FAILED,
            'error_message' => 'Unable to locate AUM value on issuer page.',
        ]);

        $this->assertSame(0, SecurityAumHistory::count());
    }

    public function test_update_now_rejects_securities_without_a_scraper(): void
    {
        $this->actingAsAdmin();

        $security = $this->security('DEFI', EtfIssuer::DEFIANCE);

        $this->postJson("/api/admin/security-data-run-update/{$security->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, DB::table('security_ingestion_batch_items')->count());
    }

    public function test_update_now_requires_admin_and_an_existing_security(): void
    {
        $security = $this->security('ULTY');

        $this->postJson("/api/admin/security-data-run-update/{$security->id}")->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['is_admin' => false]), ['*']);

        $this->postJson("/api/admin/security-data-run-update/{$security->id}")->assertStatus(401);

        $this->actingAsAdmin();

        $this->postJson('/api/admin/security-data-run-update/999999')->assertNotFound();
    }
}
