<?php

namespace Tests\Feature\Portfolios;

use App\Models\Portfolio;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowPortfolioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_view_portfolio_with_portfolio_selects(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $defaultPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Main Portfolio',
            'is_default' => true,
            'status_id' => Status::ACTIVE,
        ]);

        $secondPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Rocket',
            'is_default' => false,
            'status_id' => Status::ACTIVE,
        ]);

        $otherUser = User::factory()->create();

        Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'is_default' => true,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/view-portfolio/{$defaultPortfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.id', $defaultPortfolio->id);
        $response->assertJsonPath('data.portfolio_name', 'Main Portfolio');
        $response->assertJsonPath('data.is_default', true);
        $response->assertJsonPath('data.status_id', Status::ACTIVE);

        $portfolioSelects = $response->json('data.portfolio_selects');

        $this->assertIsArray($portfolioSelects);

        $this->assertSame(
            'Main Portfolio',
            $portfolioSelects[(string) $defaultPortfolio->id]
                ?? $portfolioSelects[$defaultPortfolio->id]
                ?? null
        );

        $this->assertSame(
            'Income Rocket',
            $portfolioSelects[(string) $secondPortfolio->id]
                ?? $portfolioSelects[$secondPortfolio->id]
                ?? null
        );

        $this->assertNotContains(
            'Other User Portfolio',
            array_values($portfolioSelects)
        );
    }

    public function test_authenticated_user_cannot_view_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/view-portfolio/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    public function test_guest_cannot_view_portfolio(): void
    {
        $response = $this->getJson('/api/view-portfolio/1');

        $response->assertStatus(401);
    }
}
