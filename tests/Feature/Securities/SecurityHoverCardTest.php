<?php

namespace Tests\Feature\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHoverCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_security_hover_card_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $security = Security::create([
            'symbol' => 'AMDY',
            'security_type_id' => 1,
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'AMD Option Income ETF',
            'distribution_frequency_id' => 2,
            'etf_issuer_id' => 1,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-20',
            'close_price' => 18.42,
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => 0.52,
            'ex_dividend_date' => '2026-05-23',
            'payment_date' => '2026-05-30',
            'data_source_id' => 1,
        ]);

        $response = $this->getJson(
            '/api/security-hover-card/AMDY'
        );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

                'data' => [

                    'symbol' => 'AMDY',

                    'security_name' => 'AMD Option Income ETF',

                    'last_close_price' => 18.42,

                    'last_dividend_amount' => 0.52,

                    'last_ex_dividend_date' => '2026-05-23',

                    'yahoo_finance_url' => 'https://finance.yahoo.com/quote/AMDY/',

                ],

            ]);
    }

    public function test_it_returns_404_when_symbol_does_not_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $this->getJson(
            '/api/security-hover-card/DOESNOTEXIST'
        )

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Security not found.',

            ]);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson(
            '/api/security-hover-card/AMDY'
        )

            ->assertStatus(401);
    }
}
