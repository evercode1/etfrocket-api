<?php

namespace Tests\Feature\Etfs;

use App\Models\Etf;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompareEtfsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_compare_etfs(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
            'fund_name' => 'Schwab U.S. Dividend Equity ETF',
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
            'fund_name' => 'Vanguard High Dividend Yield ETF',
        ]);

        $this->createPriceHistory($schd->id, '2026-05-13', 78.12);
        $this->createPriceHistory($schd->id, '2026-05-14', 78.44);

        $this->createPriceHistory($vym->id, '2026-05-13', 119.25);
        $this->createPriceHistory($vym->id, '2026-05-14', 120.10);

        $response = $this->getJson(
            "/api/compare-etfs?metric=price&range=30d&etf_ids={$schd->id},{$vym->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.metric', 'price');
        $response->assertJsonPath('data.range', '30d');

        $response->assertJsonPath('data.series.0.etf_id', $schd->id);
        $response->assertJsonPath('data.series.0.symbol', 'SCHD');
        $response->assertJsonPath('data.series.0.fund_name', 'Schwab U.S. Dividend Equity ETF');

        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-13');
        $response->assertJsonPath('data.series.0.points.0.value', '78.1200');
        $response->assertJsonPath('data.series.0.points.1.date', '2026-05-14');
        $response->assertJsonPath('data.series.0.points.1.value', '78.4400');

        $response->assertJsonPath('data.series.1.etf_id', $vym->id);
        $response->assertJsonPath('data.series.1.symbol', 'VYM');

        $response->assertJsonPath('data.series.1.points.0.date', '2026-05-13');
        $response->assertJsonPath('data.series.1.points.0.value', '119.2500');
        $response->assertJsonPath('data.series.1.points.1.date', '2026-05-14');
        $response->assertJsonPath('data.series.1.points.1.value', '120.1000');
    }

    public function test_guest_cannot_compare_etfs(): void
    {
        $response = $this->getJson('/api/compare-etfs');

        $response->assertStatus(401);
    }

    public function test_compare_etfs_uses_default_metric_and_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($etf->id, '2026-05-14', 78.44);

        $response = $this->getJson(
            "/api/compare-etfs?etf_ids={$etf->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.metric', 'price');
        $response->assertJsonPath('data.range', '1y');
        $response->assertJsonPath('data.series.0.symbol', 'SCHD');
        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-14');
        $response->assertJsonPath('data.series.0.points.0.value', '78.4400');
    }

    public function test_compare_etfs_filters_history_by_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($etf->id, '2026-04-01', 75.00);
        $this->createPriceHistory($etf->id, '2026-05-01', 77.00);
        $this->createPriceHistory($etf->id, '2026-05-14', 78.00);

        $response = $this->getJson(
            "/api/compare-etfs?metric=price&range=30d&etf_ids={$etf->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-01');
        $response->assertJsonPath('data.series.0.points.1.date', '2026-05-14');

        $this->assertCount(2, $response->json('data.series.0.points'));
    }

    public function test_compare_etfs_preserves_requested_etf_order(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $aaa = Etf::factory()->create(['symbol' => 'AAA']);
        $bbb = Etf::factory()->create(['symbol' => 'BBB']);

        $this->createPriceHistory($aaa->id, '2026-05-14', 10.00);
        $this->createPriceHistory($bbb->id, '2026-05-14', 20.00);

        $response = $this->getJson(
            "/api/compare-etfs?metric=price&range=30d&etf_ids={$bbb->id},{$aaa->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.series.0.symbol', 'BBB');
        $response->assertJsonPath('data.series.1.symbol', 'AAA');
    }

    public function test_compare_etfs_returns_500_for_invalid_metric(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/compare-etfs?metric=bad_metric&range=30d&etf_ids=1,2'
        );

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    public function test_compare_etfs_returns_500_when_no_etfs_are_provided(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/compare-etfs?metric=price&range=30d');

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    private function createPriceHistory(int $etfId, string $date, float $closePrice): void
    {
        DB::table('etf_price_histories')->insert([
            'etf_id' => $etfId,
            'price_date' => $date,
            'close_price' => $closePrice,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
