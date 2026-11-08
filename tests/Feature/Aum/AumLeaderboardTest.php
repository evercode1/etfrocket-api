<?php

namespace Tests\Feature\Aum;

use App\Http\Controllers\Public\Aum\AumLeaderboardController;
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

class AumLeaderboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-01 12:00:00');

        Cache::forget(AumLeaderboardController::CACHE_KEY);

        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        $this->truncateTables();

        Cache::forget(AumLeaderboardController::CACHE_KEY);

        Carbon::setTestNow();

        parent::tearDown();
    }

    private function truncateTables(): void
    {
        DB::table('security_aum_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('security_update_schedules')->truncate();
    }

    private function fundData(Security $security, string $date, int $aum, float $nav): void
    {
        SecurityAumHistory::create([
            'security_id' => $security->id,
            'aum_date' => $date,
            'assets_under_management' => $aum,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);

        SecurityNavHistory::create([
            'security_id' => $security->id,
            'nav_date' => $date,
            'nav_per_share' => $nav,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);
    }

    public function test_it_is_public_and_ranks_by_latest_aum_with_period_changes(): void
    {
        $big = Security::factory()->create(['symbol' => 'BIGG']);
        $small = Security::factory()->create(['symbol' => 'SMAL']);

        // BIGG: 1m ago 10,000,000 shares @ $10 -> today 12,000,000 shares @ $9.
        $this->fundData($big, '2026-03-27', 100_000_000, 10.0);
        $this->fundData($big, '2026-04-30', 108_000_000, 9.0);

        // SMAL: launched inside the 1m window.
        $this->fundData($small, '2026-04-20', 5_000_000, 20.0);
        $this->fundData($small, '2026-04-30', 6_000_000, 20.0);

        $response = $this->getJson('/api/aum-leaderboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.as_of', '2026-04-30')
            ->assertJsonPath('data.totals.aum', 114_000_000)
            ->assertJsonPath('data.totals.security_count', 2)
            ->assertJsonPath('data.securities.0.symbol', 'BIGG')
            ->assertJsonPath('data.securities.0.rank', 1)
            ->assertJsonPath('data.securities.0.aum', 108_000_000)
            ->assertJsonPath('data.securities.1.symbol', 'SMAL')
            ->assertJsonPath('data.securities.1.inception_date', '2026-04-20');

        $bigOneMonth = $response->json('data.securities.0.changes.1m');

        $this->assertSame(8_000_000, $bigOneMonth['aum_change']);
        $this->assertEquals(8.0, $bigOneMonth['aum_change_percentage']);
        // (12,000,000 - 10,000,000) shares * $9
        $this->assertSame(18_000_000, $bigOneMonth['estimated_flow']);

        $smallOneMonth = $response->json('data.securities.1.changes.1m');

        $this->assertNull($smallOneMonth['aum_change']);
        $this->assertNull($smallOneMonth['estimated_flow']);

        // SMAL existed one week ago (2026-04-23 -> uses 2026-04-20 row).
        $this->assertSame(1_000_000, $response->json('data.securities.1.changes.1w.aum_change'));
        // 250,000 -> 300,000 shares at $20.
        $this->assertSame(1_000_000, $response->json('data.securities.1.changes.1w.estimated_flow'));

        // Totals only include funds comparable over the period.
        $this->assertSame(8_000_000, $response->json('data.totals.changes.1m.aum_change'));
    }

    public function test_it_excludes_inactive_securities(): void
    {
        $active = Security::factory()->create(['symbol' => 'ACTV']);
        $inactive = Security::factory()->create([
            'symbol' => 'GONE',
            'status_id' => Status::INACTIVE,
        ]);

        $this->fundData($active, '2026-04-30', 1_000_000, 10.0);
        $this->fundData($inactive, '2026-04-30', 9_000_000, 10.0);

        $this->getJson('/api/aum-leaderboard')
            ->assertOk()
            ->assertJsonCount(1, 'data.securities')
            ->assertJsonPath('data.securities.0.symbol', 'ACTV');
    }

    public function test_it_returns_an_empty_leaderboard_without_data(): void
    {
        $this->getJson('/api/aum-leaderboard')
            ->assertOk()
            ->assertJsonPath('data.as_of', null)
            ->assertJsonCount(0, 'data.securities');
    }

    public function test_it_flags_stale_rows_and_counts_covered_etfs_awaiting_data(): void
    {
        $fresh = Security::factory()->create(['symbol' => 'FRSH']);
        $stale = Security::factory()->create(['symbol' => 'OLDD']);
        $soon = Security::factory()->create(['symbol' => 'SOON']);

        $this->fundData($fresh, '2026-04-30', 2_000_000, 10.0);
        $this->fundData($stale, '2026-04-20', 1_000_000, 10.0);

        SecurityDetail::where('security_id', $soon->id)
            ->update(['etf_issuer_id' => EtfIssuer::YIELDMAX]);

        SecurityUpdateSchedule::factory()->aum()->create([
            'security_id' => $soon->id,
        ]);

        // Test "now" is 2026-05-01: Apr 30 is current, Apr 20 is stale.
        $this->getJson('/api/aum-leaderboard')
            ->assertOk()
            ->assertJsonPath('data.pending_count', 1)
            ->assertJsonPath('data.stale_after_days', 7)
            ->assertJsonCount(2, 'data.securities')
            ->assertJsonPath('data.securities.0.symbol', 'FRSH')
            ->assertJsonPath('data.securities.0.is_stale', false)
            ->assertJsonPath('data.securities.1.symbol', 'OLDD')
            ->assertJsonPath('data.securities.1.is_stale', true);
    }
}
