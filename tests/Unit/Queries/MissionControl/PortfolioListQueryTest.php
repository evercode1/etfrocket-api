<?php

namespace Tests\Unit\Queries\MissionControl;

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

    public function test_it_returns_portfolios_for_user(): void
    {
        $user = User::factory()->create();

        $portfolioOne = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Income Rocket',
            'is_default' => 1,
        ]);

        $portfolioTwo = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Dividend Snowball',
            'is_default' => 0,
        ]);

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertCount(2, $portfolios);

        $this->assertTrue(
            $portfolios->contains('id', $portfolioOne->id)
        );

        $this->assertTrue(
            $portfolios->contains('id', $portfolioTwo->id)
        );
    }

    public function test_it_does_not_return_portfolios_for_other_users(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'User Portfolio',
        ]);

        Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Other User Portfolio',
        ]);

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertCount(1, $portfolios);
        $this->assertSame('User Portfolio', $portfolios->first()->portfolio_name);
    }

    public function test_it_returns_empty_collection_when_user_has_no_portfolios(): void
    {
        $user = User::factory()->create();

        $portfolios = (new PortfolioListQuery())->getData($user->id);

        $this->assertCount(0, $portfolios);
        $this->assertTrue($portfolios->isEmpty());
    }
}
