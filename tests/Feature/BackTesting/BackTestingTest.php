<?php

namespace Tests\Feature\BackTesting;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackTestingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_run_backtest()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2025-01-01',

            'close_price' => 150,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2025-01-01',

            'dividend_amount' => 1,

        ]);

        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => $security->id,

                'start_date' => '2024-01-01',

                'end_date' => '2025-12-31',

                'initial_investment' => 10000,

                'monthly_contribution' => 500,

                'drip_percentage' => 100,

            ]

        );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonStructure([

            'success',

            'data' => [

                'chart_rows',

                'summary' => [

                    'final_value',

                    'total_contributions',

                    'total_dividends',

                    'ending_shares',

                ],

                'analytics' => [

                    'cagr',

                    'max_drawdown',

                    'total_return_percentage',

                ],

            ],

        ]);

        $this->assertNotEmpty(

            $response->json('data.chart_rows')

        );
    }

    public function test_guest_cannot_run_backtest()
    {
        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => 1,

                'start_date' => '2024-01-01',

                'end_date' => '2025-01-01',

                'initial_investment' => 10000,

            ]

        );

        $response->assertStatus(401);
    }

    public function test_it_validates_required_fields()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(

            '/api/back-testing',

            []

        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([

            'security_id',

            'start_date',

            'end_date',

            'initial_investment',

        ]);
    }

    public function test_it_validates_end_date_after_start_date()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => 1,

                'start_date' => '2025-01-01',

                'end_date' => '2024-01-01',

                'initial_investment' => 10000,

            ]

        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([

            'end_date',

        ]);
    }

    public function test_it_validates_drip_percentage_range()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => 1,

                'start_date' => '2024-01-01',

                'end_date' => '2025-01-01',

                'initial_investment' => 10000,

                'drip_percentage' => 150,

            ]

        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([

            'drip_percentage',

        ]);
    }

    public function test_it_returns_empty_chart_rows_when_no_history_exists()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = $this->createSecurity('CHPY');

        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => $security->id,

                'start_date' => '2024-01-01',

                'end_date' => '2025-01-01',

                'initial_investment' => 10000,

            ]

        );

        $response->assertStatus(200);

        $this->assertSame(

            [],

            $response->json('data.chart_rows')

        );

        $this->assertEquals(

            0,

            $response->json('data.summary.final_value')

        );
    }

    public function test_it_returns_analytics_payload()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2025-01-01',

            'close_price' => 200,

        ]);

        $response = $this->postJson(

            '/api/back-testing',

            [

                'security_id' => $security->id,

                'start_date' => '2024-01-01',

                'end_date' => '2025-12-31',

                'initial_investment' => 10000,

            ]

        );

        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.analytics.total_return_percentage',

            100

        );

        $this->assertGreaterThan(

            0,

            $response->json('data.analytics.cagr')

        );
    }

    private function createSecurity(
        string $symbol
    ): Security {

        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}
