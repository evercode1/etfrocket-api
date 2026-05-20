<?php

namespace Tests\Unit\Queries\MissionControl;

use App\Models\Portfolio;
use App\Models\Status;
use App\Models\User;
use App\Queries\MissionControl\MissionControlQuery;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MissionControlQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_mission_control_data_with_explicit_selected_portfolio(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Default Portfolio',
            'is_default' => 1,
        ]);

        $selectedPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Selected Portfolio',
            'is_default' => 0,
        ]);

        $data = (new MissionControlQuery())->getData($selectedPortfolio->id);

        $this->assertArrayHasKey('portfolios', $data);
        $this->assertArrayHasKey('selected_portfolio', $data);
        $this->assertArrayHasKey('portfolio_snapshot', $data);
        $this->assertArrayHasKey('portfolio_flight_path', $data);
        $this->assertArrayHasKey('risk_alerts', $data);
        $this->assertArrayHasKey('watchlist', $data);
        $this->assertArrayHasKey('activity', $data);

        $this->assertCount(2, $data['portfolios']);

        $this->assertSame(
            $selectedPortfolio->id,
            $data['selected_portfolio']->id
        );

        $this->assertSame(
            'Selected Portfolio',
            $data['selected_portfolio']->portfolio_name
        );

        $this->assertSame(
            $selectedPortfolio->id,
            $data['portfolio_snapshot']['portfolio_id']
        );

        $this->assertSame(
            'Selected Portfolio',
            $data['portfolio_snapshot']['portfolio_name']
        );

        $this->assertSame([], $data['portfolio_flight_path']);
        $this->assertSame([], $data['risk_alerts']);
        $this->assertSame([], $data['watchlist']);
        $this->assertSame([], $data['activity']);
    }

    public function test_it_uses_default_portfolio_when_no_portfolio_is_selected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'First Portfolio',
            'is_default' => 0,
        ]);

        $defaultPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Default Portfolio',
            'is_default' => 1,
        ]);

        $data = (new MissionControlQuery())->getData();

        $this->assertSame(
            $defaultPortfolio->id,
            $data['selected_portfolio']->id
        );

        $this->assertSame(
            $defaultPortfolio->id,
            $data['portfolio_snapshot']['portfolio_id']
        );

        $this->assertSame([], $data['portfolio_flight_path']);
    }

    public function test_it_uses_first_portfolio_when_no_selected_or_default_portfolio_exists(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $firstPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'First Portfolio',
            'is_default' => 0,
        ]);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Second Portfolio',
            'is_default' => 0,
        ]);

        $data = (new MissionControlQuery())->getData();

        $this->assertSame(
            $firstPortfolio->id,
            $data['selected_portfolio']->id
        );

        $this->assertSame(
            $firstPortfolio->id,
            $data['portfolio_snapshot']['portfolio_id']
        );

        $this->assertSame([], $data['portfolio_flight_path']);
    }

    public function test_it_ignores_selected_portfolio_that_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $defaultPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'User Default Portfolio',
            'is_default' => 1,
        ]);

        $otherUserPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Other User Portfolio',
            'is_default' => 1,
        ]);

        $data = (new MissionControlQuery())->getData($otherUserPortfolio->id);

        $this->assertSame(
            $defaultPortfolio->id,
            $data['selected_portfolio']->id
        );

        $this->assertSame(
            $defaultPortfolio->id,
            $data['portfolio_snapshot']['portfolio_id']
        );

        $this->assertSame([], $data['portfolio_flight_path']);
    }

    public function test_it_returns_null_selected_portfolio_and_snapshot_when_user_has_no_portfolios(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $data = (new MissionControlQuery())->getData();

        $this->assertCount(0, $data['portfolios']);

        $this->assertNull($data['selected_portfolio']);
        $this->assertNull($data['portfolio_snapshot']);

        $this->assertSame([], $data['portfolio_flight_path']);
        $this->assertSame([], $data['risk_alerts']);
        $this->assertSame([], $data['watchlist']);
        $this->assertSame([], $data['activity']);
    }
}
