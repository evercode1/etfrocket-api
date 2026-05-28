<?php

namespace Tests\Feature\Etfs;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListEtfsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_filtered_etfs(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $lowEtf = Etf::factory()->create(['symbol' => 'LOW']);
        $highEtf = Etf::factory()->create(['symbol' => 'HIGH']);
        $middleEtf = Etf::factory()->create(['symbol' => 'MID']);

        $this->createMetric($lowEtf, ['total_return_percentage' => 5.25]);
        $this->createMetric($highEtf, ['total_return_percentage' => 22.75]);
        $this->createMetric($middleEtf, ['total_return_percentage' => 12.50]);

        $response = $this->getJson('/api/list-etfs?category=momentum&filter=highest_total_return_percentage&scope=all&range=1y&limit=25');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.data.0.symbol', 'HIGH');
        $response->assertJsonPath('data.data.1.symbol', 'MID');
        $response->assertJsonPath('data.data.2.symbol', 'LOW');

        $response->assertJsonPath('data.total', 3);
        $response->assertJsonPath('data.per_page', 25);
    }

    public function test_guest_cannot_list_etfs(): void
    {
        $response = $this->getJson('/api/list-etfs');

        $response->assertStatus(401);
    }

    public function test_it_uses_default_filters_when_no_query_params_are_provided(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $lowEtf = Etf::factory()->create(['symbol' => 'LOW']);
        $highEtf = Etf::factory()->create(['symbol' => 'HIGH']);

        $this->createMetric($lowEtf, ['total_return_percentage' => 3.00]);
        $this->createMetric($highEtf, ['total_return_percentage' => 15.00]);

        $response = $this->getJson('/api/list-etfs');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.data.0.symbol', 'HIGH');
        $response->assertJsonPath('data.data.1.symbol', 'LOW');
        $response->assertJsonPath('data.total', 2);
    }

    public function test_it_paginates_filtered_etfs(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        for ($i = 1; $i <= 30; $i++) {
            $etf = Etf::factory()->create([
                'symbol' => 'ETF'.$i,
            ]);

            $this->createMetric($etf, [
                'total_return_percentage' => $i,
            ]);
        }

        $response = $this->getJson('/api/list-etfs?category=momentum&filter=highest_total_return_percentage&scope=all&range=1y&limit=10');

        $response->assertStatus(200);

        $response->assertJsonPath('data.per_page', 10);
        $response->assertJsonPath('data.total', 30);
        $response->assertJsonPath('data.last_page', 3);

        $response->assertJsonPath('data.data.0.symbol', 'ETF30');
        $response->assertJsonPath('data.data.9.symbol', 'ETF21');
    }

    public function test_it_returns_500_for_invalid_filter_request(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/list-etfs?category=bad-category');

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    private function createMetric(Etf $etf, array $overrides = []): EtfMetric
    {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etf->id,
            'performance_range_type_id' => 1,

            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),

            'start_price' => 100,
            'end_price' => 110,
            'price_change' => 10,
            'price_change_percentage' => 10,

            'dividends_paid' => 0,
            'dividend_count' => 0,
            'average_dividend' => 0,

            'total_return_percentage' => 10,

            'start_nav' => 100,
            'end_nav' => 110,
            'nav_change' => 10,
            'nav_erosion_percentage' => 0,
            'nav_direction_id' => 1,

            'start_aum' => 1000000000,
            'end_aum' => 1100000000,
            'aum_change' => 100000000,
            'aum_change_percentage' => 10,
            'aum_direction_id' => 1,

            'calculated_at' => Carbon::now(),
        ], $overrides));
    }
}
