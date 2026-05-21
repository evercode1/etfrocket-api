<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\Signals\PortfolioDistributionGrowthSignalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioDistributionGrowthSignalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_empty_distribution_growth_data_when_portfolio_has_no_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertFalse($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(0, $data['growth_count']);
        $this->assertSame(0.0, $data['portfolio_income_impact']);
        $this->assertSame([], $data['affected_etfs']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_distribution_growth_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
            'dividend_count' => 12,
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(10.0, $data['portfolio_income_impact']);
        $this->assertSame(['NVII'], $data['affected_etfs']);

        $this->assertCount(1, $data['top_contributors']);
        $this->assertCount(1, $data['all_rows']);

        $row = $data['top_contributors'][0];

        $this->assertSame($etf->id, $row['etf_id']);
        $this->assertSame('NVII', $row['symbol']);
        $this->assertSame('NVII Test ETF', $row['fund_name']);
        $this->assertSame(100.0, $row['shares']);
        $this->assertSame(0.6, $row['recent_average_dividend']);
        $this->assertSame(0.5, $row['baseline_average_dividend']);
        $this->assertSame(20.0, $row['growth_percentage']);
        $this->assertSame(10.0, $row['estimated_income_impact']);
        $this->assertSame(4, $row['recent_dividend_count']);
        $this->assertSame(12, $row['baseline_dividend_count']);
    }

    public function test_distribution_growth_only_includes_positive_growth_rows(): void
    {
        $portfolio = $this->createPortfolio();

        $upEtf = $this->createEtf('UP');
        $downEtf = $this->createEtf('DOWN');

        $this->createBuyTransaction($portfolio->id, $upEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $downEtf->id, 100);

        $this->createMetric($upEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($upEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($downEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($downEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(['UP'], $data['affected_etfs']);
        $this->assertSame('UP', $data['top_contributors'][0]['symbol']);
    }

    public function test_distribution_growth_returns_no_data_when_metrics_are_missing(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('MISS');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(0, $data['growth_count']);
        $this->assertSame(0.0, $data['portfolio_income_impact']);
        $this->assertSame([], $data['affected_etfs']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_distribution_growth_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $heldEtf = $this->createEtf('HELD');
        $soldEtf = $this->createEtf('SOLD');

        $this->createBuyTransaction($portfolio->id, $heldEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $soldEtf->id, 100);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $soldEtf->id,
            'transaction_type_id' => 2,
            'shares' => 100,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        foreach ([$heldEtf, $soldEtf] as $etf) {
            $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
                'average_dividend' => '0.6000',
            ]);

            $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
                'average_dividend' => '0.5000',
            ]);
        }

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(['HELD'], $data['affected_etfs']);
        $this->assertSame('HELD', $data['top_contributors'][0]['symbol']);
    }

    public function test_distribution_growth_top_contributors_are_limited_to_three(): void
    {
        $portfolio = $this->createPortfolio();

        $firstEtf = $this->createEtf('ONE');
        $secondEtf = $this->createEtf('TWO');
        $thirdEtf = $this->createEtf('THREE');
        $fourthEtf = $this->createEtf('FOUR');

        $this->createGrowthHolding($portfolio->id, $firstEtf, 1000);
        $this->createGrowthHolding($portfolio->id, $secondEtf, 500);
        $this->createGrowthHolding($portfolio->id, $thirdEtf, 250);
        $this->createGrowthHolding($portfolio->id, $fourthEtf, 100);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(4, $data['growth_count']);
        $this->assertCount(3, $data['top_contributors']);
        $this->assertCount(4, $data['all_rows']);

        $this->assertSame(['ONE', 'TWO', 'THREE'], collect($data['top_contributors'])
            ->pluck('symbol')
            ->toArray());
    }

    public function test_it_returns_empty_distribution_decline_data_when_portfolio_has_no_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getDistributionDeclineSignalData($portfolio->id);

        $this->assertFalse($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(0, $data['decline_count']);
        $this->assertSame(0.0, $data['portfolio_income_impact']);
        $this->assertSame([], $data['affected_etfs']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_distribution_decline_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('DECL');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getDistributionDeclineSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['decline_count']);
        $this->assertSame(-10.0, $data['portfolio_income_impact']);
        $this->assertSame(['DECL'], $data['affected_etfs']);

        $row = $data['top_contributors'][0];

        $this->assertSame('DECL', $row['symbol']);
        $this->assertSame(-20.0, $row['growth_percentage']);
        $this->assertSame(-10.0, $row['estimated_income_impact']);
    }

    public function test_distribution_decline_only_includes_negative_growth_rows(): void
    {
        $portfolio = $this->createPortfolio();

        $upEtf = $this->createEtf('UP');
        $downEtf = $this->createEtf('DOWN');

        $this->createBuyTransaction($portfolio->id, $upEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $downEtf->id, 100);

        $this->createMetric($upEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($upEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($downEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($downEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getDistributionDeclineSignalData($portfolio->id);

        $this->assertSame(1, $data['decline_count']);
        $this->assertSame(['DOWN'], $data['affected_etfs']);
        $this->assertSame('DOWN', $data['top_contributors'][0]['symbol']);
    }

    public function test_it_returns_nav_metric_summary_for_portfolio(): void
    {
        $portfolio = $this->createPortfolio();

        $stableEtf = $this->createEtf('STBL');
        $watchEtf = $this->createEtf('RISK');

        $this->createBuyTransaction($portfolio->id, $stableEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $watchEtf->id, 100);

        $this->createMetric($stableEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($watchEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = app(PortfolioDistributionGrowthSignalService::class)
            ->getNavMetricSummary($portfolio->id);

        $this->assertSame('Watch', $summary['nav_health']);
        $this->assertSame(-12.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['RISK'], $summary['affected_etfs']);
    }

    public function test_nav_metric_summary_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $heldEtf = $this->createEtf('HELD');
        $soldEtf = $this->createEtf('SOLD');

        $this->createBuyTransaction($portfolio->id, $heldEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $soldEtf->id, 100);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $soldEtf->id,
            'transaction_type_id' => 2,
            'shares' => 100,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        $this->createMetric($heldEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($soldEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = app(PortfolioDistributionGrowthSignalService::class)
            ->getNavMetricSummary($portfolio->id);

        $this->assertSame('Stable', $summary['nav_health']);
        $this->assertSame(-1.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['HELD'], $summary['affected_etfs']);
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
        ]);
    }

    private function createBuyTransaction(
        int $portfolioId,
        int $etfId,
        float $shares
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'etf_id' => $etfId,
            'transaction_type_id' => 1,
            'shares' => $shares,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);
    }

    private function createGrowthHolding(
        int $portfolioId,
        Etf $etf,
        float $shares
    ): void {
        $this->createBuyTransaction($portfolioId, $etf->id, $shares);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);
    }

    private function createMetric(
        int $etfId,
        int $performanceRangeTypeId,
        array $overrides = []
    ): EtfMetric {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etfId,
            'performance_range_type_id' => $performanceRangeTypeId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
            'start_price' => '10.0000',
            'end_price' => '12.0000',
            'price_change' => '2.0000',
            'price_change_percentage' => '20.0000',
            'dividends_paid' => '1.0000',
            'dividend_count' => 1,
            'average_dividend' => '1.0000',
            'total_return_percentage' => '25.0000',
            'start_nav' => '10.0000',
            'end_nav' => '10.0000',
            'nav_change' => '0.0000',
            'nav_erosion_percentage' => '0.0000',
            'start_aum' => 1000000,
            'end_aum' => 1100000,
            'aum_change' => 100000,
            'aum_change_percentage' => '10.0000',
            'calculated_at' => now(),
        ], $overrides));
    }
}
