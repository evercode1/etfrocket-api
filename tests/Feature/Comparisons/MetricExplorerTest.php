<?php

namespace Tests\Feature\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetricExplorerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_load_metric_explorer()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = Security::factory()->create([

            'symbol' => 'CHPY',

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 22.50,

            'total_return_percentage' => 18.40,

            'aum_change_percentage' => 12.50,

            'nav_erosion_percentage' => 2.50,

        ]);

        $response = $this->getJson(

            '/api/metric-explorer'

        );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonPath(

            'data.summary.metric',

            'price_growth'

        );

        $response->assertJsonPath(

            'data.summary.range',

            '90d'

        );

        $response->assertJsonPath(

            'data.summary.results_count',

            1

        );

        $response->assertJsonPath(

            'data.table_rows.0.symbol',

            'CHPY'

        );

        $response->assertJsonPath(

            'data.table_rows.0.metric_value',

            '22.5000'

        );

        $response->assertJsonStructure([

            'success',

            'data' => [

                'summary' => [

                    'metric',

                    'range',

                    'sort_direction',

                    'results_count',

                ],

                'spotlight' => [

                    [

                        'rank',

                        'security_id',

                        'symbol',

                        'security_name',

                        'metric',

                        'metric_label',

                        'metric_value',

                        'nav_health',

                        'aum_change_percentage',

                        'total_return_percentage',

                    ],

                ],

                'table_rows' => [

                    [

                        'rank',

                        'security_id',

                        'symbol',

                        'security_name',

                        'metric',

                        'metric_label',

                        'metric_value',

                        'nav_health',

                        'aum_change_percentage',

                        'total_return_percentage',

                    ],

                ],

                'options' => [

                    'metrics',

                    'ranges',

                ],

            ],

        ]);
    }

    public function test_it_respects_metric_selection()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = Security::factory()->create([

            'symbol' => 'CHPY',

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'aum_change_percentage' => 55.55,

        ]);

        $response = $this->getJson(

            '/api/metric-explorer?metric=aum_growth'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.summary.metric',

            'aum_growth'

        );

        $response->assertJsonPath(

            'data.table_rows.0.metric_value',

            '55.5500'

        );
    }

    public function test_it_respects_range_selection()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = Security::factory()->create([

            'symbol' => 'CHPY',

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::ONE_YEAR,

            'price_change_percentage' => 88.88,

        ]);

        $response = $this->getJson(

            '/api/metric-explorer?range=1y'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.summary.range',

            '1y'

        );

        $response->assertJsonPath(

            'data.table_rows.0.metric_value',

            '88.8800'

        );
    }

    public function test_it_respects_sort_direction()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $high = Security::factory()->create([

            'symbol' => 'HIGH',

        ]);

        $low = Security::factory()->create([

            'symbol' => 'LOW',

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $high->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 50,

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $low->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 5,

        ]);

        $response = $this->getJson(

            '/api/metric-explorer?sort_direction=asc'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.table_rows.0.symbol',

            'LOW'

        );
    }

    public function test_it_respects_limit()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        foreach (range(1, 10) as $index) {

            $security = Security::factory()->create([

                'symbol' => "SEC{$index}",

            ]);

            SecurityMetric::factory()->create([

                'security_id' => $security->id,

                'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

                'price_change_percentage' => $index,

            ]);
        }

        $response = $this->getJson(

            '/api/metric-explorer?limit=5'

        );

        $response->assertStatus(200);

        $this->assertCount(

            5,

            $response->json('data.table_rows')

        );
    }

    public function test_guest_cannot_access_metric_explorer()
    {
        $response = $this->getJson(

            '/api/metric-explorer'

        );

        $response->assertStatus(401);
    }

    public function test_it_returns_empty_payload_when_no_metrics_exist()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(

            '/api/metric-explorer'

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.summary.results_count',

            0

        );

        $this->assertSame(

            [],

            $response->json('data.table_rows')

        );
    }
}
