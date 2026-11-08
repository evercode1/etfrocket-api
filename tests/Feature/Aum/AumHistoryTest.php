<?php

namespace Tests\Feature\Aum;

use App\Http\Controllers\Public\Aum\AumHistoryController;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AumHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        $this->truncateTables();

        parent::tearDown();
    }

    private function truncateTables(): void
    {
        DB::table('security_aum_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('security_update_schedules')->truncate();

        Carbon::setTestNow();

        foreach (['HIST', 'GONE', 'NOPE', 'SOON', 'UNCV'] as $symbol) {
            Cache::forget(AumHistoryController::cacheKey($symbol));
        }
    }

    private function fundData(Security $security, string $date, int $aum, ?float $nav): void
    {
        SecurityAumHistory::create([
            'security_id' => $security->id,
            'aum_date' => $date,
            'assets_under_management' => $aum,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);

        if (! is_null($nav)) {
            SecurityNavHistory::create([
                'security_id' => $security->id,
                'nav_date' => $date,
                'nav_per_share' => $nav,
                'data_source_id' => DataSource::WEB_SCRAPER,
            ]);
        }
    }

    public function test_it_returns_history_with_shares_and_daily_flows(): void
    {
        $security = Security::factory()->create(['symbol' => 'HIST']);

        $this->fundData($security, '2026-04-28', 10_000_000, 10.0); // 1,000,000 shares
        $this->fundData($security, '2026-04-29', 11_000_000, 10.0); // 1,100,000 shares
        $this->fundData($security, '2026-04-30', 10_450_000, 9.5);  // 1,100,000 shares

        $response = $this->getJson('/api/aum-history/hist');

        $response->assertOk()
            ->assertJsonPath('data.security.symbol', 'HIST')
            ->assertJsonPath('data.security.inception_date', '2026-04-28')
            ->assertJsonPath('data.latest.date', '2026-04-30')
            ->assertJsonPath('data.latest.aum', 10_450_000)
            ->assertJsonCount(3, 'data.history');

        $history = $response->json('data.history');

        $this->assertNull($history[0]['flow']);
        $this->assertSame(1_100_000, $history[1]['shares']);
        $this->assertSame(1_000_000, $history[1]['flow']);
        // NAV drop with no share change is not a flow.
        $this->assertSame(0, $history[2]['flow']);
    }

    public function test_missing_nav_leaves_shares_and_flow_null(): void
    {
        $security = Security::factory()->create(['symbol' => 'HIST']);

        $this->fundData($security, '2026-04-29', 10_000_000, 10.0);
        $this->fundData($security, '2026-04-30', 11_000_000, null);

        $history = $this->getJson('/api/aum-history/HIST')
            ->assertOk()
            ->json('data.history');

        $this->assertNull($history[1]['shares']);
        $this->assertNull($history[1]['flow']);
    }

    public function test_unknown_or_inactive_symbols_return_404(): void
    {
        Security::factory()->create([
            'symbol' => 'GONE',
            'status_id' => Status::INACTIVE,
        ]);

        $this->getJson('/api/aum-history/GONE')->assertNotFound();
        $this->getJson('/api/aum-history/NOPE')->assertNotFound();
    }

    private function makeCovered(Security $security): void
    {
        SecurityDetail::where('security_id', $security->id)
            ->update(['etf_issuer_id' => EtfIssuer::YIELDMAX]);

        SecurityUpdateSchedule::factory()->aum()->create([
            'security_id' => $security->id,
        ]);
    }

    public function test_covered_etf_without_data_is_pending(): void
    {
        $security = Security::factory()->create(['symbol' => 'SOON']);

        $this->makeCovered($security);

        $this->getJson('/api/aum-history/SOON')
            ->assertOk()
            ->assertJsonPath('data.coverage_status', 'pending')
            ->assertJsonPath('data.security.symbol', 'SOON')
            ->assertJsonPath('data.latest', null)
            ->assertJsonCount(0, 'data.history');
    }

    public function test_active_etf_without_coverage_or_data_returns_404(): void
    {
        Security::factory()->create(['symbol' => 'UNCV']);

        $this->getJson('/api/aum-history/UNCV')->assertNotFound();
    }

    public function test_coverage_status_is_current_or_stale_by_latest_date(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');

        $security = Security::factory()->create(['symbol' => 'HIST']);

        $this->fundData($security, '2026-05-04', 10_000_000, 10.0);

        // 7 days old: still current.
        $this->getJson('/api/aum-history/HIST')
            ->assertOk()
            ->assertJsonPath('data.coverage_status', 'current');

        Cache::forget(AumHistoryController::cacheKey('HIST'));
        Carbon::setTestNow('2026-05-12 12:00:00');

        // 8 days old: stale.
        $this->getJson('/api/aum-history/HIST')
            ->assertOk()
            ->assertJsonPath('data.coverage_status', 'stale');
    }
}
