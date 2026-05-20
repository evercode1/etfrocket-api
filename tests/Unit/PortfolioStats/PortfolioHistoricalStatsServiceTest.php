<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\PortfolioHistoricalStatsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioHistoricalStatsServiceTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_holdings_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $etf->id, 2, 3, 35, '2026-03-01');

        $holdings = (new PortfolioHistoricalStatsService())->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame($etf->id, $holdings->first()['etf_id']);
        $this->assertSame(15.0, $holdings->first()['shares']);
    }

    public function test_it_applies_sells_when_calculating_holdings_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('AMDY');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 2, 4, 30, '2026-02-01');

        $holdings = (new PortfolioHistoricalStatsService())->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame(6.0, $holdings->first()['shares']);
    }

    public function test_it_excludes_holdings_fully_sold_by_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('SOLD');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 2, 10, 30, '2026-02-01');

        $holdings = (new PortfolioHistoricalStatsService())->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertTrue($holdings->isEmpty());
    }

    public function test_it_does_not_include_future_transactions_in_as_of_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('TIME');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 1, 90, 30, '2026-03-01');

        $holdings = (new PortfolioHistoricalStatsService())->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-01'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame(10.0, $holdings->first()['shares']);
    }

    public function test_it_only_returns_holdings_for_requested_portfolio(): void
    {
        $portfolio = $this->createPortfolio();

        $otherPortfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($otherPortfolio->id, $etf->id, 1, 99, 25, '2026-01-01');

        $holdings = (new PortfolioHistoricalStatsService())->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-01'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame(10.0, $holdings->first()['shares']);
    }

    public function test_it_calculates_shares_owned_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $etf->id, 2, 4, 35, '2026-03-01');

        $shares = (new PortfolioHistoricalStatsService())->getSharesOwnedAsOfDate(
            $portfolio->id,
            $etf->id,
            '2026-02-15'
        );

        $this->assertSame(15.0, $shares);
    }

    public function test_it_calculates_portfolio_value_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $firstEtf = $this->createEtf('NVII');
        $secondEtf = $this->createEtf('JEPI');

        $this->createTransaction($portfolio->id, $firstEtf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $secondEtf->id, 1, 5, 50, '2026-01-01');

        $this->createPrice($firstEtf->id, '2026-01-15', 30);
        $this->createPrice($secondEtf->id, '2026-01-15', 40);

        $value = (new PortfolioHistoricalStatsService())->getPortfolioValueAsOfDate(
            $portfolio->id,
            '2026-01-31'
        );

        // NVII: 10 * 30 = 300
        // JEPI: 5 * 40 = 200
        $this->assertSame(500.0, $value);
    }

    public function test_it_uses_latest_price_before_or_on_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('PRICE');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');

        $this->createPrice($etf->id, '2026-01-01', 20);
        $this->createPrice($etf->id, '2026-01-15', 30);
        $this->createPrice($etf->id, '2026-02-01', 99);

        $value = (new PortfolioHistoricalStatsService())->getPortfolioValueAsOfDate(
            $portfolio->id,
            '2026-01-31'
        );

        $this->assertSame(300.0, $value);
    }

    public function test_it_skips_holdings_without_price_history(): void
    {
        $portfolio = $this->createPortfolio();

        $pricedEtf = $this->createEtf('YESP');
        $unpricedEtf = $this->createEtf('NOPR');

        $this->createTransaction($portfolio->id, $pricedEtf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $unpricedEtf->id, 1, 10, 25, '2026-01-01');

        $this->createPrice($pricedEtf->id, '2026-01-15', 30);

        $value = (new PortfolioHistoricalStatsService())->getPortfolioValueAsOfDate(
            $portfolio->id,
            '2026-01-31'
        );

        $this->assertSame(300.0, $value);
    }

    public function test_it_calculates_dividend_income_between_dates_using_shares_owned_on_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('DIV');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 1, 5, 30, '2026-01-20');

        $this->createDividend($etf->id, '0.5000', '2026-01-15');
        $this->createDividend($etf->id, '1.0000', '2026-01-25');

        $income = (new PortfolioHistoricalStatsService())->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        // 2026-01-15: 10 shares * .50 = 5
        // 2026-01-25: 15 shares * 1.00 = 15
        $this->assertSame(20.0, $income);
    }

    public function test_it_excludes_dividends_when_no_shares_were_owned_on_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('LATE');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-02-01');

        $this->createDividend($etf->id, '1.0000', '2026-01-15');

        $income = (new PortfolioHistoricalStatsService())->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        $this->assertSame(0.0, $income);
    }

    public function test_it_excludes_dividends_after_position_was_sold_before_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('SELLD');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $etf->id, 2, 10, 30, '2026-01-10');

        $this->createDividend($etf->id, '1.0000', '2026-01-15');

        $income = (new PortfolioHistoricalStatsService())->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        $this->assertSame(0.0, $income);
    }

    public function test_it_only_counts_dividends_inside_date_range(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('RANGE');

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25, '2026-01-01');

        $this->createDividend($etf->id, '1.0000', '2026-01-15');
        $this->createDividend($etf->id, '1.0000', '2026-02-15');

        $income = (new PortfolioHistoricalStatsService())->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        $this->assertSame(10.0, $income);
    }

    private function createPortfolio(): Portfolio
    {
        $user = User::factory()->create();

        return Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createEtf(string $symbol): Etf
    {
        return Etf::factory()->create([
            'symbol' => $symbol,
            'fund_name' => "{$symbol} Test ETF",
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);
    }

    private function createTransaction(
        int $portfolioId,
        int $etfId,
        int $transactionTypeId,
        float $shares,
        float $pricePerShare,
        string $transactionDate
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'etf_id' => $etfId,
            'transaction_type_id' => $transactionTypeId,
            'shares' => $shares,
            'price_per_share' => $pricePerShare,
            'transaction_date' => $transactionDate,
        ]);
    }

    private function createPrice(
        int $etfId,
        string $priceDate,
        float $closePrice
    ): EtfPriceHistory {
        return EtfPriceHistory::factory()->create([
            'etf_id' => $etfId,
            'price_date' => $priceDate,
            'close_price' => $closePrice,
            'volume' => 100000,
        ]);
    }

    private function createDividend(
        int $etfId,
        string $amount,
        string $exDividendDate
    ): EtfDividendHistory {
        return EtfDividendHistory::factory()->create([
            'etf_id' => $etfId,
            'dividend_amount' => $amount,
            'ex_dividend_date' => $exDividendDate,
            'payment_date' => $exDividendDate,
            'data_source_id' => 1,
        ]);
    }
}
