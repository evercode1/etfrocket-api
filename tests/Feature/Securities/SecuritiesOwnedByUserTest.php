<?php

namespace Tests\Feature\Securities;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecuritiesOwnedByUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_securities_owned_by_portfolio_sorted_by_symbol(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $vym = Security::factory()->create(['symbol' => 'VYM']);
        $schd = Security::factory()->create(['symbol' => 'SCHD']);
        $qqqi = Security::factory()->create(['symbol' => 'QQQI']);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $vym->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $qqqi->id,
        ]);

        $response = $this->getJson("/api/list-securities-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'id' => $qqqi->id,
                    'symbol' => 'QQQI',
                ],
                [
                    'id' => $schd->id,
                    'symbol' => 'SCHD',
                ],
                [
                    'id' => $vym->id,
                    'symbol' => 'VYM',
                ],
            ],
        ]);

        $this->assertSame(
            [
                'QQQI',
                'SCHD',
                'VYM',
            ],
            collect($response->json('data'))->pluck('symbol')->toArray()
        );
    }

    public function test_it_returns_each_security_only_once_even_when_multiple_transactions_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
        ]);

        $response = $this->getJson("/api/list-securities-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'id' => $schd->id,
                    'symbol' => 'SCHD',
                ],
            ],
        ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_does_not_include_securities_from_other_portfolios(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPortfolio = Portfolio::factory()->create();

        $schd = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vym = Security::factory()->create([
            'symbol' => 'VYM',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $otherPortfolio->id,
            'security_id' => $vym->id,
        ]);

        $response = $this->getJson("/api/list-securities-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'id' => $schd->id,
                    'symbol' => 'SCHD',
                ],
            ],
        ]);

        $this->assertFalse(
            collect($response->json('data'))
                ->pluck('id')
                ->contains($vym->id)
        );
    }

    public function test_it_returns_empty_data_when_portfolio_has_no_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/list-securities-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
    }

    public function test_guest_cannot_list_securities_owned_by_user(): void
    {
        $response = $this->getJson('/api/list-securities-owned-by-user/1');

        $response->assertStatus(401);
    }
}
