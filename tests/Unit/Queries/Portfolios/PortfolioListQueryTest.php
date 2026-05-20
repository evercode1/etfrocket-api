<?php

namespace Tests\Unit\Queries\Portfolios;

use App\Models\Portfolio;
use App\Models\Status;
use App\Models\User;
use App\Queries\MissionControl\PortfolioListQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioListQueryTest extends TestCase
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

    public function test_it_returns_only_portfolios_for_the_given_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Rocket',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Dividend Core',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertCount(2, $portfolios);

        $this->assertSame(
            ['Dividend Core', 'Income Rocket'],
            $portfolios->pluck('portfolio_name')->toArray()
        );
    }

    public function test_it_orders_default_portfolio_first_then_by_name(): void
    {
        $user = User::factory()->create();

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Zulu Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Alpha Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Main Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertSame(
            ['Main Portfolio', 'Alpha Portfolio', 'Zulu Portfolio'],
            $portfolios->pluck('portfolio_name')->toArray()
        );
    }

    public function test_it_returns_empty_collection_when_user_has_no_portfolios(): void
    {
        $user = User::factory()->create();

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertTrue($portfolios->isEmpty());
    }
}
