<?php

namespace Tests\Feature\Etfs;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EtfsOwnedByUserTest extends TestCase
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

    public function test_authenticated_user_can_list_etfs_owned_by_portfolio_sorted_by_symbol(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $qqqi = Etf::factory()->create([
            'symbol' => 'QQQI',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $vym->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $qqqi->id,
        ]);

        $response = $this->getJson("/api/list-etfs-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                (string) $qqqi->id => 'QQQI',
                (string) $schd->id => 'SCHD',
                (string) $vym->id => 'VYM',
            ],
        ]);

        $this->assertSame(
            [
                $qqqi->id,
                $schd->id,
                $vym->id,
            ],
            array_keys($response->json('data'))
        );
    }

    public function test_it_returns_each_etf_only_once_even_when_multiple_transactions_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
        ]);

        $response = $this->getJson("/api/list-etfs-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                (string) $schd->id => 'SCHD',
            ],
        ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_does_not_include_etfs_from_other_portfolios(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPortfolio = Portfolio::factory()->create();

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $otherPortfolio->id,
            'etf_id' => $vym->id,
        ]);

        $response = $this->getJson("/api/list-etfs-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                (string) $schd->id => 'SCHD',
            ],
        ]);

        $this->assertArrayNotHasKey(
            (string) $vym->id,
            $response->json('data')
        );
    }

    public function test_it_returns_empty_data_when_portfolio_has_no_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/list-etfs-owned-by-user/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
    }

    public function test_guest_cannot_list_etfs_owned_by_user(): void
    {
        $response = $this->getJson('/api/list-etfs-owned-by-user/1');

        $response->assertStatus(401);
    }
}
