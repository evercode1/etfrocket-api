<?php

namespace Tests\Feature\Comparisons;

use App\Models\Etf;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class CompareSymbolsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    public function tearDown(): void
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

            'data.table_rows.0.symbol',

            'CHPY'

        );

        $response->assertJsonStructure([

            'success',

            'data' => [

                'summary' => [

                    'compared_etfs_count',

                ],

                'table_rows' => [

                    [

                        'etf_id',

                        'symbol',

                        'fund_name',

                        'latest_price',

                        'nav_health',

                        'aum_change_percentage_30_day',

                        'total_return_percentage_90_day',

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

                ],

            ],

        ]);
    }

    public function test_guest_cannot_compare_symbols()
    {
        $response = $this->getJson(

            '/api/compare-symbols?symbols[]=CHPY'

        );

        $response->assertStatus(401);
    }
}
