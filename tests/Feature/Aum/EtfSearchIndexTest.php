<?php

namespace Tests\Feature\Aum;

use App\Http\Controllers\Public\Aum\EtfSearchIndexController;
use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use App\Models\EtfIssuer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtfSearchIndexTest extends TestCase
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
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('security_update_schedules')->truncate();

        Cache::forget(EtfSearchIndexController::CACHE_KEY);
    }

    private function withAum(Security $security): void
    {
        SecurityAumHistory::create([
            'security_id' => $security->id,
            'aum_date' => '2026-04-30',
            'assets_under_management' => 1_000_000,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);
    }

    public function test_it_lists_active_securities_with_aum_data_sorted_by_symbol(): void
    {
        $zeta = Security::factory()->create(['symbol' => 'ZETA']);
        $alfa = Security::factory()->create(['symbol' => 'ALFA']);
        $noData = Security::factory()->create(['symbol' => 'NODA']);
        $inactive = Security::factory()->create([
            'symbol' => 'GONE',
            'status_id' => Status::INACTIVE,
        ]);

        SecurityDetail::where('security_id', $alfa->id)->update([
            'security_name' => 'Alfa Income ETF',
        ]);

        $this->withAum($zeta);
        $this->withAum($alfa);
        $this->withAum($inactive);

        $this->getJson('/api/etf-search-index')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.symbol', 'ALFA')
            ->assertJsonPath('data.0.name', 'Alfa Income ETF')
            ->assertJsonPath('data.1.symbol', 'ZETA');

        $this->assertNotNull($noData);
    }

    public function test_it_includes_covered_etfs_awaiting_data_as_pending(): void
    {
        $withData = Security::factory()->create(['symbol' => 'DATA']);
        $soon = Security::factory()->create(['symbol' => 'SOON']);

        SecurityDetail::where('security_id', $soon->id)
            ->update(['etf_issuer_id' => EtfIssuer::YIELDMAX]);

        SecurityUpdateSchedule::factory()->aum()->create([
            'security_id' => $soon->id,
        ]);

        // Issuer without a scraper: not covered even with a schedule.
        $defiance = Security::factory()->create(['symbol' => 'DEFI']);

        SecurityDetail::where('security_id', $defiance->id)
            ->update(['etf_issuer_id' => EtfIssuer::DEFIANCE]);

        SecurityUpdateSchedule::factory()->aum()->create([
            'security_id' => $defiance->id,
        ]);

        SecurityAumHistory::create([
            'security_id' => $withData->id,
            'aum_date' => now()->toDateString(),
            'assets_under_management' => 1_000_000,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);

        $this->getJson('/api/etf-search-index')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.symbol', 'DATA')
            ->assertJsonPath('data.0.status', 'current')
            ->assertJsonPath('data.1.symbol', 'SOON')
            ->assertJsonPath('data.1.status', 'pending');
    }
}
