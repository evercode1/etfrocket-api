<?php

namespace Tests\Feature\Comparisons;

use App\Models\Etf;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompareSymbolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_compare_symbols()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        Etf::factory()->create([

            'symbol' => 'CHPY',

            'fund_name' => 'CHPY Test ETF',

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

                    'metrics' => [

                        [

                            'label',

                            'value',

                        ],

                    ],

                    'ranges' => [

                        [

                            'label',

                            'value',

                        ],

                    ],

                ],

            ],

        ]);

        $this->assertIsArray(
            $response->json('data.chart_rows')
        );

        $this->assertIsArray(
            $response->json('data.invalid_symbols')
        );

        $this->assertIsArray(
            $response->json('data.options.metrics')
        );

        $this->assertIsArray(
            $response->json('data.options.ranges')
        );

        $this->assertEquals(
            'price',
            $response->json('data.options.metrics.0.value')
        );

        $this->assertEquals(
            '90d',
            $response->json('data.options.ranges.2.value')
        );
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

    public function test_it_returns_chart_rows()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        \App\Models\EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => now(),

            'close_price' => 55.12,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.chart_rows.0.CHPY',

            55.12

        );

        $response->assertJsonStructure([

            'data' => [

                'chart_rows' => [

                    [

                        'date',

                        'CHPY',

                    ],

                ],

            ],

        ]);
    }

    public function test_it_respects_metric_selection()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        \App\Models\EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            \App\Models\PerformanceRangeType::NINETY_DAY,

            'aum_change_percentage' => 8.80,

        ]);

        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY&metric=aum'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.summary.selected_metric',

            'aum'

        );

        $response->assertJsonPath(

            'data.table_rows.0.chart_value',

            '8.8000'

        );
    }

    public function test_it_respects_range_selection()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

        ]);

        \App\Models\EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            \App\Models\PerformanceRangeType::ONE_YEAR,

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
