<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityMetric;
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
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
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
        $this->assertSame([], $data['affected_securities']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_distribution_growth_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
            'dividend_count' => 12,
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(10.0, $data['portfolio_income_impact']);
        $this->assertSame(['NVII'], $data['affected_securities']);

        $this->assertCount(1, $data['top_contributors']);
        $this->assertCount(1, $data['all_rows']);

        $row = $data['top_contributors'][0];

        $this->assertSame($security->id, $row['security_id']);
        $this->assertSame('NVII', $row['symbol']);
        $this->assertSame('NVII_name', $row['security_name']);
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

        $upSecurity = $this->createSecurity('UP');
        $downSecurity = $this->createSecurity('DOWN');

        $this->createBuyTransaction($portfolio->id, $upSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $downSecurity->id, 100);

        $this->createMetric($upSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($upSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($downSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($downSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(['UP'], $data['affected_securities']);
        $this->assertSame('UP', $data['top_contributors'][0]['symbol']);
    }

    public function test_distribution_growth_returns_no_data_when_metrics_are_missing(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('MISS');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(0, $data['growth_count']);
        $this->assertSame(0.0, $data['portfolio_income_impact']);
        $this->assertSame([], $data['affected_securities']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_distribution_growth_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $heldSecurity = $this->createSecurity('HELD');
        $soldSecurity = $this->createSecurity('SOLD');

        $this->createBuyTransaction($portfolio->id, $heldSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $soldSecurity->id, 100);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $soldSecurity->id,
            'transaction_type_id' => 2,
            'shares' => 100,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        foreach ([$heldSecurity, $soldSecurity] as $security) {
            $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
                'average_dividend' => '0.6000',
            ]);

            $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
                'average_dividend' => '0.5000',
            ]);
        }

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(1, $data['growth_count']);
        $this->assertSame(['HELD'], $data['affected_securities']);
        $this->assertSame('HELD', $data['top_contributors'][0]['symbol']);
    }

    public function test_distribution_growth_top_contributors_are_limited_to_three(): void
    {
        $portfolio = $this->createPortfolio();

        $firstSecurity = $this->createSecurity('ONE');
        $secondSecurity = $this->createSecurity('TWO');
        $thirdSecurity = $this->createSecurity('THREE');
        $fourthSecurity = $this->createSecurity('FOUR');

        $this->createGrowthHolding($portfolio->id, $firstSecurity, 1000);
        $this->createGrowthHolding($portfolio->id, $secondSecurity, 500);
        $this->createGrowthHolding($portfolio->id, $thirdSecurity, 250);
        $this->createGrowthHolding($portfolio->id, $fourthSecurity, 100);

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
        $this->assertSame([], $data['affected_securities']);
        $this->assertSame([], $data['top_contributors']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_distribution_decline_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('DECL');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getDistributionDeclineSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['decline_count']);
        $this->assertSame(-10.0, $data['portfolio_income_impact']);
        $this->assertSame(['DECL'], $data['affected_securities']);

        $row = $data['top_contributors'][0];

        $this->assertSame('DECL', $row['symbol']);
        $this->assertSame(-20.0, $row['growth_percentage']);
        $this->assertSame(-10.0, $row['estimated_income_impact']);
    }

    public function test_distribution_decline_only_includes_negative_growth_rows(): void
    {
        $portfolio = $this->createPortfolio();

        $upSecurity = $this->createSecurity('UP');
        $downSecurity = $this->createSecurity('DOWN');

        $this->createBuyTransaction($portfolio->id, $upSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $downSecurity->id, 100);

        $this->createMetric($upSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($upSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($downSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($downSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $data = app(PortfolioDistributionGrowthSignalService::class)
            ->getDistributionDeclineSignalData($portfolio->id);

        $this->assertSame(1, $data['decline_count']);
        $this->assertSame(['DOWN'], $data['affected_securities']);
        $this->assertSame('DOWN', $data['top_contributors'][0]['symbol']);
    }

    public function test_it_returns_nav_metric_summary_for_portfolio(): void
    {
        $portfolio = $this->createPortfolio();

        $stableSecurity = $this->createSecurity('STBL');
        $watchSecurity = $this->createSecurity('RISK');

        $this->createBuyTransaction($portfolio->id, $stableSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $watchSecurity->id, 100);

        $this->createMetric($stableSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($watchSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = app(PortfolioDistributionGrowthSignalService::class)
            ->getNavMetricSummary($portfolio->id);

        $this->assertSame('Watch', $summary['nav_health']);
        $this->assertSame(-12.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['RISK'], $summary['affected_securities']);
    }

    public function test_nav_metric_summary_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $heldSecurity = $this->createSecurity('HELD');
        $soldSecurity = $this->createSecurity('SOLD');

        $this->createBuyTransaction($portfolio->id, $heldSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $soldSecurity->id, 100);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $soldSecurity->id,
            'transaction_type_id' => 2,
            'shares' => 100,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        $this->createMetric($heldSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($soldSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = app(PortfolioDistributionGrowthSignalService::class)
            ->getNavMetricSummary($portfolio->id);

        $this->assertSame('Stable', $summary['nav_health']);
        $this->assertSame(-1.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['HELD'], $summary['affected_securities']);
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

    private function createGrowthHolding(
        int $portfolioId,
        Security $security,
        float $shares
    ): void {
        $this->createBuyTransaction($portfolioId, $security->id, $shares);

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);
    }

    private function createMetric(
        int $securityId,
        int $performanceRangeTypeId,
        array $overrides = []
    ): SecurityMetric {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $securityId,
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
