<?php

namespace Tests\Unit\Queries\MissionControl;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Queries\MissionControl\PortfolioFlightPathQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioFlightPathQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow('2026-05-18');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_portfolio_has_no_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Empty Portfolio',
        ]);

        $results = app(PortfolioFlightPathQuery::class)->getData($portfolio->id);

        $this->assertSame([], $results);
    }

    public function test_it_calculates_monthly_portfolio_value_and_income(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Income Rocket',
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-03-15',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-03-31',
            'close_price' => '25.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-30',
            'close_price' => '30.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '35.0000',
            'volume' => 100000,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-04-10',
            'payment_date' => '2026-04-11',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.6000',
            'ex_dividend_date' => '2026-05-10',
            'payment_date' => '2026-05-11',
            'data_source_id' => 1,
        ]);

        $results = app(PortfolioFlightPathQuery::class)->getData($portfolio->id);

        $this->assertCount(3, $results);

        $this->assertSame('Mar 2026', $results[0]['date']);
        $this->assertSame(250.0, $results[0]['value']);
        $this->assertSame(0.0, $results[0]['income']);

        $this->assertSame('Apr 2026', $results[1]['date']);
        $this->assertSame(300.0, $results[1]['value']);
        $this->assertSame(5.0, $results[1]['income']);

        $this->assertSame('May 2026', $results[2]['date']);
        $this->assertSame(350.0, $results[2]['value']);
        $this->assertSame(6.0, $results[2]['income']);
    }

    public function test_it_subtracts_sell_transactions_from_portfolio_value(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Sell Test Portfolio',
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'AMDY',
            'fund_name' => 'AMDY Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-03-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 2,
            'shares' => 4,
            'price_per_share' => 25,
            'transaction_date' => '2026-04-15',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-03-31',
            'close_price' => '20.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-30',
            'close_price' => '30.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '40.0000',
            'volume' => 100000,
        ]);

        $results = app(PortfolioFlightPathQuery::class)->getData($portfolio->id);

        $this->assertSame(200.0, $results[0]['value']);
        $this->assertSame(180.0, $results[1]['value']);
        $this->assertSame(240.0, $results[2]['value']);
    }

    public function test_it_subtracts_sell_transactions_before_dividend_income_is_calculated(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Dividend Sell Test Portfolio',
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
            'fund_name' => 'CHPY Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-03-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 2,
            'shares' => 4,
            'price_per_share' => 25,
            'transaction_date' => '2026-04-05',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-03-31',
            'close_price' => '20.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-30',
            'close_price' => '30.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '40.0000',
            'volume' => 100000,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-04-10',
            'payment_date' => '2026-04-11',
            'data_source_id' => 1,
        ]);

        $results = app(PortfolioFlightPathQuery::class)->getData($portfolio->id);

        $this->assertSame(3.0, $results[1]['income']);
    }

    public function test_it_uses_latest_price_on_or_before_month_end(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Price Fallback Portfolio',
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'GOOY',
            'fund_name' => 'GOOY Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-03-01',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-03-20',
            'close_price' => '22.0000',
            'volume' => 100000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-20',
            'close_price' => '24.0000',
            'volume' => 100000,
        ]);

        $results = app(PortfolioFlightPathQuery::class)->getData($portfolio->id);

        $this->assertSame(220.0, $results[0]['value']);
        $this->assertSame(240.0, $results[1]['value']);
        $this->assertSame(240.0, $results[2]['value']);
    }
}
