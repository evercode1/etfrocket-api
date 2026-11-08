<?php

namespace Tests\Feature\Console;

use App\Http\Controllers\Public\Aum\AumLeaderboardController;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackfillTappAlphaHistoryCommandTest extends TestCase
{
    private const TABLES = [
        'securities',
        'security_details',
        'security_aum_histories',
        'security_nav_histories',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-08 12:00:00');

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

    private function security(string $symbol, int $issuerId = EtfIssuer::TAPPALPHA, int $statusId = Status::ACTIVE): Security
    {
        $security = Security::factory()->create(['symbol' => $symbol, 'status_id' => $statusId]);

        SecurityDetail::where('security_id', $security->id)->update(['etf_issuer_id' => $issuerId]);

        return $security;
    }

    private function fakeApi(array $chartByTicker): void
    {
        Http::fake(function ($request) use ($chartByTicker) {
            $ticker = $request['ticker'];

            return Http::response([
                'latest' => ['ticker' => $ticker],
                'chart' => $chartByTicker[$ticker] ?? [],
                'mode' => 'live',
            ], 200);
        });
    }

    private function chart(string $ticker): array
    {
        return [
            ['ticker' => $ticker, 'as_of_date' => '2026-11-05', 'nav' => 23.80, 'net_assets' => 460000000.40],
            ['ticker' => $ticker, 'as_of_date' => '2026-11-04', 'nav' => 23.70, 'net_assets' => 455000000.00],
            ['ticker' => $ticker, 'as_of_date' => '2026-11-06', 'nav' => 23.95, 'net_assets' => 471343830.71],
            // Invalid points are skipped.
            ['ticker' => $ticker, 'as_of_date' => '2026-11-03', 'nav' => null, 'net_assets' => 1000],
            ['ticker' => $ticker, 'as_of_date' => '2026-12-01', 'nav' => 24.00, 'net_assets' => 1000],
            ['ticker' => 'OTHER', 'as_of_date' => '2026-11-02', 'nav' => 24.00, 'net_assets' => 1000],
        ];
    }

    public function test_it_inserts_missing_history_and_never_overwrites_existing_rows(): void
    {
        $tdaq = $this->security('TDAQ');

        // Already scraped: must not be overwritten.
        SecurityAumHistory::create([
            'security_id' => $tdaq->id,
            'aum_date' => '2026-11-06',
            'assets_under_management' => 999,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);

        $this->fakeApi(['TDAQ' => $this->chart('TDAQ')]);

        Cache::put(AumLeaderboardController::CACHE_KEY, ['stale' => true], 600);

        $this->artisan('securities:backfill-tappalpha-history')->assertSuccessful();

        $this->assertSame(
            ['2026-11-04', '2026-11-05', '2026-11-06'],
            SecurityAumHistory::where('security_id', $tdaq->id)->orderBy('aum_date')->pluck('aum_date')->map->toDateString()->all()
        );

        $this->assertDatabaseHas('security_aum_histories', [
            'security_id' => $tdaq->id,
            'aum_date' => '2026-11-05',
            'assets_under_management' => 460000000,
        ]);

        // Existing scraped value kept.
        $this->assertDatabaseHas('security_aum_histories', [
            'security_id' => $tdaq->id,
            'aum_date' => '2026-11-06',
            'assets_under_management' => 999,
        ]);

        $this->assertSame(3, SecurityNavHistory::where('security_id', $tdaq->id)->count());
        $this->assertFalse(Cache::has(AumLeaderboardController::CACHE_KEY));

        // Re-running adds nothing.
        $this->artisan('securities:backfill-tappalpha-history')->assertSuccessful();

        $this->assertSame(3, SecurityAumHistory::where('security_id', $tdaq->id)->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->security('TDAQ');

        $this->fakeApi(['TDAQ' => $this->chart('TDAQ')]);

        $this->artisan('securities:backfill-tappalpha-history', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, SecurityAumHistory::count());
        $this->assertSame(0, SecurityNavHistory::count());
    }

    public function test_it_only_backfills_active_tappalpha_securities_and_honors_symbol(): void
    {
        $tdaq = $this->security('TDAQ');
        $tspy = $this->security('TSPY');
        $retired = $this->security('TDAX', statusId: Status::RETIRED);
        $other = $this->security('ULTY', EtfIssuer::YIELDMAX);

        $this->fakeApi([
            'TDAQ' => $this->chart('TDAQ'),
            'TSPY' => $this->chart('TSPY'),
            'TDAX' => $this->chart('TDAX'),
            'ULTY' => $this->chart('ULTY'),
        ]);

        $this->artisan('securities:backfill-tappalpha-history', ['--symbol' => 'tdaq'])->assertSuccessful();

        $this->assertSame(3, SecurityAumHistory::where('security_id', $tdaq->id)->count());
        $this->assertSame(0, SecurityAumHistory::where('security_id', $tspy->id)->count());

        $this->artisan('securities:backfill-tappalpha-history')->assertSuccessful();

        $this->assertSame(3, SecurityAumHistory::where('security_id', $tspy->id)->count());
        $this->assertSame(0, SecurityAumHistory::where('security_id', $retired->id)->count());
        $this->assertSame(0, SecurityAumHistory::where('security_id', $other->id)->count());

        $this->artisan('securities:backfill-tappalpha-history', ['--symbol' => 'ULTY'])->assertFailed();
    }

    public function test_api_failures_are_reported_per_fund(): void
    {
        $this->security('TDAQ');

        Http::fake(['*' => Http::response('', 500)]);

        $this->artisan('securities:backfill-tappalpha-history')
            ->expectsOutputToContain('Unable to retrieve TappAlpha fund data.')
            ->assertFailed();

        $this->assertSame(0, SecurityAumHistory::count());
    }
}
