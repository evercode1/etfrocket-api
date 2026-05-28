<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioDividendStatsServiceTest extends TestCase
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

    public function test_it_returns_zero_monthly_income_for_empty_holdings(): void
    {
        $income = (new PortfolioDividendStatsService)->getMonthlyDividendIncome(
            collect(),
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(0.0, $income);
    }

    public function test_it_calculates_monthly_dividend_income_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $weeklyEtf = $this->createEtf('NVII', 2);
        $monthlyEtf = $this->createEtf('JEPI', 4);

        $this->createTransaction($portfolio->id, $weeklyEtf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $monthlyEtf->id, 1, 5, 50);

        $this->createDividend($weeklyEtf->id, '0.5000', '2026-05-15');
        $this->createDividend($weeklyEtf->id, '0.4000', '2026-05-08');
        $this->createDividend($monthlyEtf->id, '1.0000', '2026-05-01');

        $holdings = $this->getHoldings($portfolio->id);

        $income = (new PortfolioDividendStatsService)->getMonthlyDividendIncome(
            $holdings,
            Carbon::parse('2026-05-01')
        );

        // Weekly ETF: 10 shares * (.50 + .40) = 9.00
        // Monthly ETF: 5 shares * 1.00 = 5.00
        // Total = 14.00
        $this->assertSame(14.0, $income);
    }

    public function test_it_only_counts_dividends_in_requested_month(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '0.5000', '2026-05-15');
        $this->createDividend($etf->id, '0.3000', '2026-04-15');
        $this->createDividend($etf->id, '0.7000', '2026-06-15');

        $holdings = $this->getHoldings($portfolio->id);

        $income = (new PortfolioDividendStatsService)->getMonthlyDividendIncome(
            $holdings,
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(5.0, $income);
    }

    public function test_it_calculates_average_recent_monthly_income_using_non_zero_months(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '1.0000', '2026-05-15');
        $this->createDividend($etf->id, '0.5000', '2026-04-15');

        $holdings = $this->getHoldings($portfolio->id);

        $average = (new PortfolioDividendStatsService)->getAverageRecentMonthlyIncome(
            $holdings,
            3
        );

        // May: 10 * 1.00 = 10
        // April: 10 * .50 = 5
        // March: 0 and excluded
        // Average = 7.5
        $this->assertSame(7.5, $average);
    }

    public function test_projected_monthly_income_uses_average_recent_monthly_income(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '1.0000', '2026-05-15');
        $this->createDividend($etf->id, '0.5000', '2026-04-15');

        $holdings = $this->getHoldings($portfolio->id);

        $projectedMonthlyIncome = (new PortfolioDividendStatsService)
            ->getProjectedMonthlyIncome($holdings);

        $this->assertSame(7.5, $projectedMonthlyIncome);
    }

    public function test_it_returns_zero_average_recent_monthly_income_when_no_dividend_history_exists(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NODEV', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $holdings = $this->getHoldings($portfolio->id);

        $average = (new PortfolioDividendStatsService)->getAverageRecentMonthlyIncome(
            $holdings,
            3
        );

        $this->assertSame(0.0, $average);
    }

    public function test_it_calculates_dividend_growth_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '1.0000', '2026-03-15');
        $this->createDividend($etf->id, '1.5000', '2026-04-15');

        $holdings = $this->getHoldings($portfolio->id);

        $growth = (new PortfolioDividendStatsService)->getDividendGrowthPercentage(
            $holdings
        );

        $this->assertSame(50.0, $growth);
    }

    public function test_it_calculates_negative_dividend_growth_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '1.0000', '2026-03-15');
        $this->createDividend($etf->id, '0.7500', '2026-04-15');

        $holdings = $this->getHoldings($portfolio->id);

        $growth = (new PortfolioDividendStatsService)->getDividendGrowthPercentage(
            $holdings
        );

        $this->assertSame(-25.0, $growth);
    }

    public function test_it_returns_null_growth_when_previous_month_has_no_income(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $this->createDividend($etf->id, '1.0000', '2026-05-15');

        $holdings = $this->getHoldings($portfolio->id);

        $growth = (new PortfolioDividendStatsService)->getDividendGrowthPercentage(
            $holdings
        );

        $this->assertNull($growth);
    }

    public function test_it_returns_null_growth_when_holdings_are_empty(): void
    {
        $growth = (new PortfolioDividendStatsService)->getDividendGrowthPercentage(
            collect()
        );

        $this->assertNull($growth);
    }

    public function test_it_calculates_forward_yield_percentage(): void
    {
        $holdings = collect([
            [
                'etf_id' => 1,
                'symbol' => 'NVII',
                'shares' => 10,
                'cost_basis' => 250.0,
            ],
            [
                'etf_id' => 2,
                'symbol' => 'JEPI',
                'shares' => 5,
                'cost_basis' => 250.0,
            ],
        ]);

        $yield = (new PortfolioDividendStatsService)->getForwardYieldPercentage(
            $holdings,
            20.0
        );

        // Annualized income: 20 * 12 = 240
        // Cost basis: 500
        // Yield: 48%
        $this->assertSame(48.0, $yield);
    }

    public function test_it_returns_null_forward_yield_when_cost_basis_is_zero(): void
    {
        $holdings = collect([
            [
                'etf_id' => 1,
                'symbol' => 'NVII',
                'shares' => 10,
                'cost_basis' => 0.0,
            ],
        ]);

        $yield = (new PortfolioDividendStatsService)->getForwardYieldPercentage(
            $holdings,
            20.0
        );

        $this->assertNull($yield);
    }

    public function test_it_returns_latest_dividend_date_for_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $firstEtf = $this->createEtf('NVII', 2);
        $secondEtf = $this->createEtf('JEPI', 4);

        $this->createTransaction($portfolio->id, $firstEtf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $secondEtf->id, 1, 10, 25);

        $this->createDividend($firstEtf->id, '0.5000', '2026-04-15');
        $this->createDividend($secondEtf->id, '1.0000', '2026-05-15');

        $holdings = $this->getHoldings($portfolio->id);

        $latestDividendDate = (new PortfolioDividendStatsService)
            ->getLatestDividendDate($holdings);

        $this->assertSame('2026-05-15', $latestDividendDate);
    }

    public function test_it_returns_null_latest_dividend_date_when_no_history_exists(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NODEV', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $holdings = $this->getHoldings($portfolio->id);

        $latestDividendDate = (new PortfolioDividendStatsService)
            ->getLatestDividendDate($holdings);

        $this->assertNull($latestDividendDate);
    }

    public function test_it_uses_current_holdings_not_original_transaction_shares(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('SELL', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $etf->id, 2, 4, 30);

        $this->createDividend($etf->id, '1.0000', '2026-05-15');

        $holdings = $this->getHoldings($portfolio->id);

        $income = (new PortfolioDividendStatsService)->getMonthlyDividendIncome(
            $holdings,
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(6.0, $income);
    }

    private function createPortfolio(): Portfolio
    {
        $user = User::factory()->create();

        return Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createEtf(string $symbol, int $frequencyId): Etf
    {
        return Etf::factory()->create([
            'symbol' => $symbol,
            'fund_name' => "{$symbol} Test ETF",
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => $frequencyId,
        ]);
    }

    private function createTransaction(
        int $portfolioId,
        int $etfId,
        int $transactionTypeId,
        float $shares,
        float $pricePerShare
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'etf_id' => $etfId,
            'transaction_type_id' => $transactionTypeId,
            'shares' => $shares,
            'price_per_share' => $pricePerShare,
            'transaction_date' => '2026-01-01',
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
            'payment_date' => Carbon::parse($exDividendDate)->addDay()->toDateString(),
            'data_source_id' => 1,
        ]);
    }

    private function getHoldings(int $portfolioId): Collection
    {
        return (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolioId
        );
    }

    public function test_it_returns_projected_income_timeline_with_growth(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII', 2);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $holdings = $this->getHoldings($portfolio->id);

        $timeline = (new PortfolioDividendStatsService)
            ->getProjectedIncomeTimeline($holdings);

        $this->assertCount(5, $timeline);

        $this->assertSame(
            ['May', 'Jun', 'Jul', 'Aug', 'Sep'],
            collect($timeline)->pluck('month')->toArray()
        );

        // Base projected monthly income:
        // 10 shares * 1.00 = 10.00
        //
        // Then compounded monthly at 8% annual:
        // Month 0 = 10.00
        // Month 1 ≈ 10.07
        // Month 2 ≈ 10.13
        // Month 3 ≈ 10.20
        // Month 4 ≈ 10.27

        $this->assertSame(10.00, $timeline[0]['income']);
        $this->assertSame(10.07, $timeline[1]['income']);
        $this->assertSame(10.13, $timeline[2]['income']);
        $this->assertSame(10.20, $timeline[3]['income']);
        $this->assertSame(10.27, $timeline[4]['income']);
    }
}
