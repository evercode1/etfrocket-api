<?php

namespace Tests\Feature\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DividendCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_dividend_calendar_events(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '1.2500',
            'ex_dividend_date' => '2026-05-25',
            'payment_date' => '2026-05-27',
            'data_source_id' => 1,
        ]);

        $response = $this->getJson("/api/dividend-calendar/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'events' => [
                    '*' => [
                        'security_id',
                        'symbol',
                        'security_name',
                        'shares',
                        'distribution_amount',
                        'estimated_payment_amount',
                        'ex_dividend_date',
                        'payment_date',
                        'status',
                        'note',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('data.events.0.security_id', $security->id);
        $response->assertJsonPath('data.events.0.symbol', 'NVII');
        $response->assertJsonPath('data.events.0.shares', 10);
        $response->assertJsonPath('data.events.0.distribution_amount', 1.25);
        $response->assertJsonPath('data.events.0.estimated_payment_amount', 12.5);
        $response->assertJsonPath('data.events.0.ex_dividend_date', '2026-05-25');
        $response->assertJsonPath('data.events.0.payment_date', '2026-05-27');
        $response->assertJsonPath('data.events.0.status', 'Declared');
    }

    public function test_dividend_calendar_returns_expected_event_when_not_declared_yet(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'QQQI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 40,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.7500',
            'ex_dividend_date' => '2026-05-13',
            'payment_date' => '2026-05-15',
            'data_source_id' => 1,
        ]);

        $response = $this->getJson("/api/dividend-calendar/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.events.0.symbol', 'QQQI');
        $response->assertJsonPath('data.events.0.status', 'Expected');
        $response->assertJsonPath('data.events.0.distribution_amount', null);
        $response->assertJsonPath('data.events.0.estimated_payment_amount', null);
        $response->assertJsonPath('data.events.0.ex_dividend_date', '2026-05-20');
        $response->assertJsonPath('data.events.0.payment_date', null);
    }

    public function test_dividend_calendar_returns_empty_events_when_portfolio_has_no_weekly_holdings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $monthlySecurity = Security::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $monthlySecurity->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        $response = $this->getJson("/api/dividend-calendar/{$portfolio->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSame([], $response->json('data.events'));
    }

    public function test_guest_cannot_get_dividend_calendar(): void
    {
        $response = $this->getJson('/api/dividend-calendar/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_dividend_calendar_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/dividend-calendar/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}
