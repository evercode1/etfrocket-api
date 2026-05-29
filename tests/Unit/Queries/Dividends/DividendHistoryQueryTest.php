<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\DividendHistoryQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DividendHistoryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_paid_dividend_history_for_current_portfolio_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklySecurity = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $weeklySecurity->id,
            'distribution_frequency_id' => 2,
        ]);

        $monthlySecurity = Security::create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $monthlySecurity->id,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $weeklySecurity->id, 10);
        $this->createBuyTransaction($portfolio->id, $monthlySecurity->id, 5);

        SecurityDividendHistory::factory()->create([
            'security_id' => $weeklySecurity->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $monthlySecurity->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-04-30',
            'payment_date' => '2026-05-05',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(10.0, $result['total_paid']);
        $this->assertSame(10.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);

        $dividends = $result['dividends'];

        $this->assertSame(2, $dividends->total());

        $rows = collect($dividends->items());

        $this->assertSame('NVII', $rows[0]->symbol);
        $this->assertSame('2026-05-15', $rows[0]->ex_dividend_date);
        $this->assertSame('2026-05-16', $rows[0]->payment_date);
        $this->assertSame('0.5000', (string) $rows[0]->dividend_amount);
        $this->assertSame(10.0, $rows[0]->shares_owned);
        $this->assertSame(5.0, $rows[0]->estimated_payment_amount);

        $this->assertSame('JEPI', $rows[1]->symbol);
        $this->assertSame('2026-04-30', $rows[1]->ex_dividend_date);
        $this->assertSame('2026-05-05', $rows[1]->payment_date);
        $this->assertSame('1.0000', (string) $rows[1]->dividend_amount);
        $this->assertSame(5.0, $rows[1]->shares_owned);
        $this->assertSame(5.0, $rows[1]->estimated_payment_amount);
    }

    public function test_it_calculates_month_to_date_and_last_month_paid_totals(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $security->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.3000',
            'ex_dividend_date' => '2026-03-15',
            'payment_date' => '2026-03-16',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(12.0, $result['total_paid']);
        $this->assertSame(5.0, $result['month_to_date_paid']);
        $this->assertSame(4.0, $result['last_month_paid']);
    }

    public function test_it_excludes_unpaid_dividend_history(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $security->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => null,
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(0.0, $result['total_paid']);
        $this->assertSame(0.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(0, $result['dividends']->total());
    }

    public function test_it_excludes_dividends_for_securities_never_owned_by_portfolio(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $heldSecurity = Security::create([
            'symbol' => 'HELD',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $heldSecurity->id,
            'distribution_frequency_id' => 2,
        ]);

        $notHeldSecurity = Security::create([
            'symbol' => 'NOPE',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $notHeldSecurity->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $heldSecurity->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $heldSecurity->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $notHeldSecurity->id,
            'dividend_amount' => '9.9900',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(4.0, $result['total_paid']);
        $this->assertSame(4.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(1, $result['dividends']->total());
        $this->assertSame('HELD', $result['dividends']->items()[0]->symbol);
        $this->assertSame(10.0, $result['dividends']->items()[0]->shares_owned);
        $this->assertSame(4.0, $result['dividends']->items()[0]->estimated_payment_amount);
    }

    public function test_it_excludes_dividends_when_position_was_sold_before_ex_date(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $security->id, 10);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(0.0, $result['total_paid']);
        $this->assertSame(0.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(0, $result['dividends']->total());
    }

    public function test_it_includes_dividends_for_positions_owned_on_ex_date_even_if_sold_later(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'PAID',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $security->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 30,
            'transaction_date' => '2026-06-01',
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(5.0, $result['total_paid']);
        $this->assertSame(5.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(1, $result['dividends']->total());
        $this->assertSame('PAID', $result['dividends']->items()[0]->symbol);
        $this->assertSame(10.0, $result['dividends']->items()[0]->shares_owned);
        $this->assertSame(5.0, $result['dividends']->items()[0]->estimated_payment_amount);
    }

    public function test_it_filters_by_symbol(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $nvi = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $nvi->id,
            'distribution_frequency_id' => 2,
        ]);

        $jepi = Security::create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $jepi->id,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $nvi->id, 10);
        $this->createBuyTransaction($portfolio->id, $jepi->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $nvi->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $jepi->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request([
                'symbol' => 'NV',
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(5.0, $result['total_paid']);
        $this->assertSame(5.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(1, $result['dividends']->total());
        $this->assertSame('NVII', $result['dividends']->items()[0]->symbol);
    }

    public function test_it_filters_by_frequency_id(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklySecurity = Security::create([
            'symbol' => 'WEEK',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $weeklySecurity->id,
            'distribution_frequency_id' => 2,
        ]);

        $monthlySecurity = Security::create([
            'symbol' => 'MONTH',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $monthlySecurity->id,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $weeklySecurity->id, 10);
        $this->createBuyTransaction($portfolio->id, $monthlySecurity->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $weeklySecurity->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $monthlySecurity->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request([
                'frequency_id' => 4,
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(10.0, $result['total_paid']);
        $this->assertSame(10.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(1, $result['dividends']->total());
        $this->assertSame('MONTH', $result['dividends']->items()[0]->symbol);
    }

    public function test_it_filters_by_ex_dividend_date_range(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $security->id, 10);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = app(DividendHistoryQuery::class)->getData(
            new Request([
                'date_from' => '2026-05-01',
                'date_to' => '2026-05-31',
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(5.0, $result['total_paid']);
        $this->assertSame(5.0, $result['month_to_date_paid']);
        $this->assertSame(0.0, $result['last_month_paid']);
        $this->assertSame(1, $result['dividends']->total());
        $this->assertSame('2026-05-15', $result['dividends']->items()[0]->ex_dividend_date);
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(DividendHistoryQuery::class)->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );
    }

    private function createBuyTransaction(
        int $portfolioId,
        int $securityId,
        float $shares
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'security_id' => $securityId,
            'transaction_type_id' => 1,
            'shares' => $shares,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);
    }
}
