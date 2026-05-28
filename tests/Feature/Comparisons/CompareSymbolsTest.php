<?php

namespace Tests\Feature\Comparisons;

use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfDividendHistory;
use App\Models\EtfMetric;
use App\Models\EtfNavHistory;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompareSymbolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_compare_symbols()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

            'fund_name' => 'CHPY Test ETF',

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => now(),

            'close_price' => 55.12,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 24.80,

            'aum_change_percentage' => 12.50,

            'nav_erosion_percentage' => 3.50,

            'price_change_percentage' => 5.25,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY'

        );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonPath(

            'data.summary.compared_etfs_count',

            1

        );

        $response->assertJsonPath(

            'data.summary.selected_metric',

            'price'

        );

        $response->assertJsonPath(

            'data.summary.selected_range',

            '90d'

        );

        $response->assertJsonPath(

            'data.table_rows.0.symbol',

            'CHPY'

        );

        $response->assertJsonPath(

            'data.table_rows.0.latest_price',

            '55.1200'

        );

        $response->assertJsonPath(

            'data.table_rows.0.aum_change_percentage',

            '12.5000'

        );

        $response->assertJsonPath(

            'data.table_rows.0.total_return_percentage',

            '24.8000'

        );

        $response->assertJsonPath(

            'data.table_rows.0.nav_erosion_percentage',

            '3.5000'

        );

        $response->assertJsonPath(

            'data.table_rows.0.price_change_percentage',

            '5.2500'

        );

        $response->assertJsonPath(

            'data.table_rows.0.chart_value',

            '55.1200'

        );

        $response->assertJsonStructure([

            'success',

            'data' => [

                'summary' => [

                    'compared_etfs_count',

                    'selected_metric',

                    'selected_range',

                ],

                'invalid_symbols',

                'table_rows' => [

                    [

                        'etf_id',

                        'symbol',

                        'fund_name',

                        'selected_metric',

                        'selected_range',

                        'latest_price',

                        'nav_health',

                        'aum_change_percentage',

                        'total_return_percentage',

                        'nav_erosion_percentage',

                        'price_change_percentage',

                        'chart_value',

                    ],

                ],

                'chart_rows',

                'options' => [

                    'metrics',

                    'ranges',

                ],

            ],

        ]);
    }

    public function test_it_returns_invalid_symbols()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&symbols[]=FAKE'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.invalid_symbols.0',

            'FAKE'

        );
    }

    public function test_guest_cannot_compare_symbols()
    {
        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY'

        );

        $response->assertStatus(401);
    }

    public function test_it_returns_price_chart_rows()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => now(),

            'close_price' => 55.12,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&metric=price'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.chart_rows.0.CHPY',

            55.12

        );
    }

    public function test_it_returns_income_chart_rows()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => now(),

            'dividend_amount' => 1.50,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&metric=income'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.chart_rows.0.CHPY',

            1.50

        );
    }

    public function test_it_returns_nav_chart_rows()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        EtfNavHistory::factory()->create([

            'etf_id' => $etf->id,

            'nav_date' => now(),

            'nav_per_share' => 48.25,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&metric=nav'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.chart_rows.0.CHPY',

            48.25

        );
    }

    public function test_it_returns_aum_chart_rows()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => now(),

            'assets_under_management' => 100000000,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&metric=aum'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.chart_rows.0.CHPY',

            100000000

        );
    }

    public function test_it_respects_range_selection()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' => PerformanceRangeType::ONE_YEAR,

            'total_return_percentage' => 44.44,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&range=1y'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.summary.selected_range',

            '1y'

        );

        $response->assertJsonPath(

            'data.table_rows.0.total_return_percentage',

            '44.4400'

        );
    }
}
