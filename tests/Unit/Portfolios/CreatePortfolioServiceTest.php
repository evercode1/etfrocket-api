<?php

namespace Tests\Unit\Portfolios;

use App\Models\Portfolio;
use App\Models\Status;
use App\Models\User;
use App\Services\Portfolios\CreatePortfolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreatePortfolioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_creates_a_portfolio(): void
    {
        $user = User::factory()->create();

        $user_id = $user->id;

        Sanctum::actingAs($user, ['*']);

        $request = new Request([
            'portfolio_name' => 'Income Rocket',
            'is_default' => false,
        ]);

        $portfolio = (new CreatePortfolioService)->create($user_id, $request->all());

        $this->assertInstanceOf(Portfolio::class, $portfolio);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Income Rocket',
            'is_default' => 1,
        ]);
    }

    public function test_first_portfolio_is_default_even_when_is_default_is_false(): void
    {
        $user = User::factory()->create();

        $user_id = $user->id;

        Sanctum::actingAs($user, ['*']);

        $request = new Request([
            'portfolio_name' => 'First Portfolio',
            'is_default' => false,
        ]);

        $portfolio = (new CreatePortfolioService)->create($user_id, $request->all());

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'user_id' => $user->id,
            'portfolio_name' => 'First Portfolio',
            'is_default' => 1,
        ]);
    }

    public function test_it_creates_non_default_portfolio_when_user_already_has_default(): void
    {
        $user = User::factory()->create();

        $user_id = $user->id;

        Sanctum::actingAs($user, ['*']);

        $existingDefault = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Existing Default',
            'is_default' => 1,
        ]);

        $request = new Request([
            'portfolio_name' => 'Second Portfolio',
            'is_default' => false,
        ]);

        $portfolio = (new CreatePortfolioService)->create($user_id, $request->all());

        $this->assertDatabaseHas('portfolios', [
            'id' => $existingDefault->id,
            'user_id' => $user->id,
            'is_default' => 1,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'user_id' => $user->id,
            'portfolio_name' => 'Second Portfolio',
            'is_default' => 0,
        ]);
    }

    public function test_it_sets_new_portfolio_as_default_and_clears_existing_default(): void
    {
        $user = User::factory()->create();

        $user_id = $user->id;

        Sanctum::actingAs($user, ['*']);

        $existingDefault = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Existing Default',
            'is_default' => 1,
        ]);

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Other Portfolio',
            'is_default' => 0,
        ]);

        $request = new Request([
            'portfolio_name' => 'New Default',
            'is_default' => true,
        ]);

        $portfolio = (new CreatePortfolioService)->create($user_id, $request->all());

        $this->assertDatabaseHas('portfolios', [
            'id' => $existingDefault->id,
            'user_id' => $user->id,
            'is_default' => 0,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $otherPortfolio->id,
            'user_id' => $user->id,
            'is_default' => 0,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'user_id' => $user->id,
            'portfolio_name' => 'New Default',
            'is_default' => 1,
        ]);
    }

    public function test_it_only_clears_default_portfolios_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $user_id = $user->id;

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $userDefault = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'User Default',
            'is_default' => 1,
        ]);

        $otherUserDefault = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Other User Default',
            'is_default' => 1,
        ]);

        $request = new Request([
            'portfolio_name' => 'New User Default',
            'is_default' => true,
        ]);

        $portfolio = (new CreatePortfolioService)->create($user_id, $request->all());

        $this->assertDatabaseHas('portfolios', [
            'id' => $userDefault->id,
            'user_id' => $user->id,
            'is_default' => 0,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'user_id' => $user->id,
            'is_default' => 1,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $otherUserDefault->id,
            'user_id' => $otherUser->id,
            'is_default' => 1,
        ]);
    }
}
