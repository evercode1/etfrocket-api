<?php

namespace Tests\Feature\Portfolios;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowPortfolioTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_show_their_portfolio_transaction(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 75.25,
            'transaction_date' => '2026-05-15',
        ]);

        $response = $this->getJson("/api/show-portfolio-transaction/{$transaction->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'portfolio_id' => $portfolio->id,
                'etf_id' => $etf->id,
                'transaction_type_id' => 1,
                'shares' => '10.0000',
                'price_per_share' => '75.2500',
                'transaction_date' => '2026-05-15',
                'symbol' => 'SCHD',
            ],
        ]);
    }

    public function test_guest_cannot_show_portfolio_transaction(): void
    {
        $response = $this->getJson('/api/show-portfolio-transaction/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_show_another_users_portfolio_transaction(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $otherPortfolio->id,
            'etf_id' => $etf->id,
        ]);

        $response = $this->getJson("/api/show-portfolio-transaction/{$transaction->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_show_portfolio_transaction_returns_error_for_missing_transaction(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/show-portfolio-transaction/999999');

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
        ]);
    }
}
