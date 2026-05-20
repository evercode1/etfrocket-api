<?php

namespace Tests\Feature\Dividends;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DividendIntelligenceFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_dividend_intelligence(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-13',
            'payment_date' => '2026-05-15',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.2500',
            'ex_dividend_date' => '2026-05-25',
            'payment_date' => '2026-05-27',
            'data_source_id' => 1,
        ]);

        $response = $this->getJson("/api/dividend-intelligence/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.portfolio.id', $portfolio->id);
        $response->assertJsonPath('data.portfolio.name', 'Income Portfolio');
        $response->assertJsonPath('data.portfolio.has_holdings', true);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'portfolio' => [
                    'id',
                    'name',
                    'has_holdings',
                ],
                'summary' => [
                    'projected_monthly_income',
                    'upcoming_weekly_events_count',
                    'forward_yield_percentage',
                    'dividend_growth_percentage',
                ],
                'income_timeline' => [
                    '*' => [
                        'month',
                        'income',
                    ],
                ],
                'upcoming_weekly_dividends' => [
                    '*' => [
                        'etf_id',
                        'symbol',
                        'fund_name',
                        'shares',
                        'distribution_amount',
                        'estimated_payment_amount',
                        'ex_dividend_date',
                        'payment_date',
                        'status',
                        'note',
                    ],
                ],
                'additional_weekly_events_count',
                'signals' => [
                    '*' => [
                        'title',
                        'message',
                        'affected_etfs',
                        'observation',
                        'possible_causes',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('data.upcoming_weekly_dividends.0.symbol', 'NVII');
        $response->assertJsonPath('data.upcoming_weekly_dividends.0.status', 'Declared');
        $response->assertJsonPath('data.signals.0.title', 'Distribution Growth');
    }

    public function test_authenticated_user_gets_empty_dividend_intelligence_for_portfolio_with_no_holdings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/dividend-intelligence/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.portfolio.id', $portfolio->id);
        $response->assertJsonPath('data.portfolio.name', 'Empty Portfolio');
        $response->assertJsonPath('data.portfolio.has_holdings', false);

        $response->assertJsonPath('data.summary.projected_monthly_income', 0);
        $response->assertJsonPath('data.summary.upcoming_weekly_events_count', 0);
        $response->assertJsonPath('data.summary.forward_yield_percentage', null);
        $response->assertJsonPath('data.summary.dividend_growth_percentage', null);

        $this->assertSame([], $response->json('data.income_timeline'));
        $this->assertSame([], $response->json('data.upcoming_weekly_dividends'));
        $this->assertSame(0, $response->json('data.additional_weekly_events_count'));
        $this->assertSame([], $response->json('data.signals'));
    }

    public function test_guest_cannot_get_dividend_intelligence(): void
    {
        $response = $this->getJson('/api/dividend-intelligence/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_dividend_intelligence_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/dividend-intelligence/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}
