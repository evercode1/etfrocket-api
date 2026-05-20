<?php

namespace Tests\Feature\Portfolios;

use App\Models\Portfolio;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetPortfolioSelectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('personal_access_tokens')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('personal_access_tokens')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_portfolio_selects(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolioOne = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Income Rocket',
        ]);

        $portfolioTwo = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Dividend Snowball',
        ]);

        $response = $this->getJson('/api/get-portfolio-selects');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath("data.{$portfolioOne->id}", 'Income Rocket')
            ->assertJsonPath("data.{$portfolioTwo->id}", 'Dividend Snowball');
    }

    public function test_it_does_not_return_other_users_portfolios(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $userPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'My Portfolio',
        ]);

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Other User Portfolio',
        ]);

        $response = $this->getJson('/api/get-portfolio-selects');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath("data.{$userPortfolio->id}", 'My Portfolio')
            ->assertJsonMissing([
                (string) $otherPortfolio->id => 'Other User Portfolio',
            ]);
    }

    public function test_it_returns_empty_array_when_user_has_no_portfolios(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-portfolio-selects');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    public function test_guest_cannot_get_portfolio_selects(): void
    {
        $response = $this->getJson('/api/get-portfolio-selects');

        $response->assertUnauthorized();
    }
}
