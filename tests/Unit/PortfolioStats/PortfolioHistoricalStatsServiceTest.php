<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
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
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_holdings_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 3, 35, '2026-03-01');

        $holdings = (new PortfolioHistoricalStatsService)->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame($security->id, $holdings->first()['security_id']);
        $this->assertSame(15.0, $holdings->first()['shares']);
    }

    public function test_it_applies_sells_when_calculating_holdings_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('AMDY');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 4, 30, '2026-02-01');

        $holdings = (new PortfolioHistoricalStatsService)->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame(6.0, $holdings->first()['shares']);
    }

    public function test_it_excludes_holdings_fully_sold_by_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('SOLD');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 10, 30, '2026-02-01');

        $holdings = (new PortfolioHistoricalStatsService)->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-15'
        );

        $this->assertTrue($holdings->isEmpty());
    }

    public function test_it_does_not_include_future_transactions_in_as_of_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('TIME');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 90, 30, '2026-03-01');

        $holdings = (new PortfolioHistoricalStatsService)->getHoldingsAsOfDate(
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

        $security = $this->createSecurity('NVII');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($otherPortfolio->id, $security->id, 1, 99, 25, '2026-01-01');

        $holdings = (new PortfolioHistoricalStatsService)->getHoldingsAsOfDate(
            $portfolio->id,
            '2026-02-01'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame(10.0, $holdings->first()['shares']);
    }

    public function test_it_calculates_shares_owned_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 4, 35, '2026-03-01');

        $shares = (new PortfolioHistoricalStatsService)->getSharesOwnedAsOfDate(
            $portfolio->id,
            $security->id,
            '2026-02-15'
        );

        $this->assertSame(15.0, $shares);
    }

    public function test_it_calculates_portfolio_value_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $firstSecurity = $this->createSecurity('NVII');
        $secondSecurity = $this->createSecurity('JEPI');

        $this->createTransaction($portfolio->id, $firstSecurity->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $secondSecurity->id, 1, 5, 50, '2026-01-01');

        $this->createPrice($firstSecurity->id, '2026-01-15', 30);
        $this->createPrice($secondSecurity->id, '2026-01-15', 40);

        $value = (new PortfolioHistoricalStatsService)->getPortfolioValueAsOfDate(
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

        $security = $this->createSecurity('PRICE');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');

        $this->createPrice($security->id, '2026-01-01', 20);
        $this->createPrice($security->id, '2026-01-15', 30);
        $this->createPrice($security->id, '2026-02-01', 99);

        $value = (new PortfolioHistoricalStatsService)->getPortfolioValueAsOfDate(
            $portfolio->id,
            '2026-01-31'
        );

        $this->assertSame(300.0, $value);
    }

    public function test_it_skips_holdings_without_price_history(): void
    {
        $portfolio = $this->createPortfolio();

        $pricedSecurity = $this->createSecurity('YESP');
        $unpricedSecurity = $this->createSecurity('NOPR');

        $this->createTransaction($portfolio->id, $pricedSecurity->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $unpricedSecurity->id, 1, 10, 25, '2026-01-01');

        $this->createPrice($pricedSecurity->id, '2026-01-15', 30);

        $value = (new PortfolioHistoricalStatsService)->getPortfolioValueAsOfDate(
            $portfolio->id,
            '2026-01-31'
        );

        $this->assertSame(300.0, $value);
    }

    public function test_it_calculates_dividend_income_between_dates_using_shares_owned_on_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('DIV');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-01-20');

        $this->createDividend($security->id, '0.5000', '2026-01-15');
        $this->createDividend($security->id, '1.0000', '2026-01-25');

        $income = (new PortfolioHistoricalStatsService)->getDividendIncomeBetweenDates(
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

        $security = $this->createSecurity('LATE');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-02-01');

        $this->createDividend($security->id, '1.0000', '2026-01-15');

        $income = (new PortfolioHistoricalStatsService)->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        $this->assertSame(0.0, $income);
    }

    public function test_it_excludes_dividends_after_position_was_sold_before_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('SELLD');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 10, 30, '2026-01-10');

        $this->createDividend($security->id, '1.0000', '2026-01-15');

        $income = (new PortfolioHistoricalStatsService)->getDividendIncomeBetweenDates(
            $portfolio->id,
            '2026-01-01',
            '2026-01-31'
        );

        $this->assertSame(0.0, $income);
    }

    public function test_it_only_counts_dividends_inside_date_range(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('RANGE');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');

        $this->createDividend($security->id, '1.0000', '2026-01-15');
        $this->createDividend($security->id, '1.0000', '2026-02-15');

        $income = (new PortfolioHistoricalStatsService)->getDividendIncomeBetweenDates(
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

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([
            'symbol' => $symbol,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createTransaction(
        int $portfolioId,
        int $securityId,
        int $transactionTypeId,
        float $shares,
        float $pricePerShare,
        string $transactionDate
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'security_id' => $securityId,
            'transaction_type_id' => $transactionTypeId,
            'shares' => $shares,
            'price_per_share' => $pricePerShare,
            'transaction_date' => $transactionDate,
        ]);
    }

    private function createPrice(
        int $securityId,
        string $priceDate,
        float $closePrice
    ): SecurityPriceHistory {
        return SecurityPriceHistory::factory()->create([
            'security_id' => $securityId,
            'price_date' => $priceDate,
            'close_price' => $closePrice,
            'volume' => 100000,
        ]);
    }

    private function createDividend(
        int $securityId,
        string $amount,
        string $exDividendDate
    ): SecurityDividendHistory {
        return SecurityDividendHistory::factory()->create([
            'security_id' => $securityId,
            'dividend_amount' => $amount,
            'ex_dividend_date' => $exDividendDate,
            'payment_date' => $exDividendDate,
            'data_source_id' => 1,
        ]);
    }
}
