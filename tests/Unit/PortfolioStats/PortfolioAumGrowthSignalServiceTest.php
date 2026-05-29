<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\Signals\PortfolioAumGrowthSignalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioAumGrowthSignalServiceTest extends TestCase
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

    public function test_it_returns_empty_signal_when_portfolio_has_no_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertFalse($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $data['range_type_id']);
        $this->assertSame(0, $data['positive_flow_count']);
        $this->assertSame(0, $data['negative_flow_count']);
        $this->assertSame([], $data['strongest_inflows']);
        $this->assertSame([], $data['strongest_outflows']);
        $this->assertSame([], $data['affected_securities']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_empty_signal_when_holdings_have_no_aum_metrics(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $data['range_type_id']);
        $this->assertSame(0, $data['positive_flow_count']);
        $this->assertSame(0, $data['negative_flow_count']);
        $this->assertSame([], $data['strongest_inflows']);
        $this->assertSame([], $data['strongest_outflows']);
        $this->assertSame([], $data['affected_securities']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_aum_growth_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $inflowSecurity = $this->createSecurity('INFL');
        $outflowSecurity = $this->createSecurity('OUTF');

        $this->createBuyTransaction($portfolio->id, $inflowSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $outflowSecurity->id, 50);

        $this->createMetric($inflowSecurity->id, [
            'start_aum' => 100000000,
            'end_aum' => 125000000,
            'aum_change' => 25000000,
            'aum_change_percentage' => '25.0000',
            'aum_direction_id' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-01',
        ]);

        $this->createMetric($outflowSecurity->id, [
            'start_aum' => 200000000,
            'end_aum' => 180000000,
            'aum_change' => -20000000,
            'aum_change_percentage' => '-10.0000',
            'aum_direction_id' => 2,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-01',
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $data['range_type_id']);
        $this->assertSame(1, $data['positive_flow_count']);
        $this->assertSame(1, $data['negative_flow_count']);
        $this->assertSame(['INFL', 'OUTF'], $data['affected_securities']);
        $this->assertCount(2, $data['all_rows']);

        $inflow = $data['strongest_inflows'][0];

        $this->assertSame($inflowSecurity->id, $inflow['security_id']);
        $this->assertSame('INFL', $inflow['symbol']);
        $this->assertSame('INFL_name', $inflow['security_name']);
        $this->assertSame(100.0, $inflow['shares']);
        $this->assertSame(100000000, $inflow['start_aum']);
        $this->assertSame(125000000, $inflow['end_aum']);
        $this->assertSame(25000000, $inflow['aum_change']);
        $this->assertSame(25.0, $inflow['aum_change_percentage']);
        $this->assertSame(1, $inflow['aum_direction_id']);
        $this->assertSame('2026-04-01', $inflow['start_date']);
        $this->assertSame('2026-05-01', $inflow['end_date']);

        $outflow = $data['strongest_outflows'][0];

        $this->assertSame($outflowSecurity->id, $outflow['security_id']);
        $this->assertSame('OUTF', $outflow['symbol']);
        $this->assertSame(-10.0, $outflow['aum_change_percentage']);
        $this->assertSame(-20000000, $outflow['aum_change']);
    }

    public function test_it_sorts_strongest_inflows_by_highest_aum_growth_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $smallGrowthSecurity = $this->createSecurity('SMOL');
        $largeGrowthSecurity = $this->createSecurity('BIGG');

        $this->createBuyTransaction($portfolio->id, $smallGrowthSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $largeGrowthSecurity->id, 100);

        $this->createMetric($smallGrowthSecurity->id, [
            'aum_change_percentage' => '5.0000',
            'aum_change' => 5000000,
        ]);

        $this->createMetric($largeGrowthSecurity->id, [
            'aum_change_percentage' => '18.0000',
            'aum_change' => 18000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(2, $data['positive_flow_count']);
        $this->assertSame('BIGG', $data['strongest_inflows'][0]['symbol']);
        $this->assertSame('SMOL', $data['strongest_inflows'][1]['symbol']);
    }

    public function test_it_sorts_strongest_outflows_by_largest_decline_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $smallDeclineSecurity = $this->createSecurity('SMOL');
        $largeDeclineSecurity = $this->createSecurity('BIGD');

        $this->createBuyTransaction($portfolio->id, $smallDeclineSecurity->id, 100);
        $this->createBuyTransaction($portfolio->id, $largeDeclineSecurity->id, 100);

        $this->createMetric($smallDeclineSecurity->id, [
            'aum_change_percentage' => '-4.0000',
            'aum_change' => -4000000,
        ]);

        $this->createMetric($largeDeclineSecurity->id, [
            'aum_change_percentage' => '-22.0000',
            'aum_change' => -22000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(2, $data['negative_flow_count']);
        $this->assertSame('BIGD', $data['strongest_outflows'][0]['symbol']);
        $this->assertSame('SMOL', $data['strongest_outflows'][1]['symbol']);
    }

    public function test_it_limits_strongest_inflows_and_outflows_to_three_each(): void
    {
        $portfolio = $this->createPortfolio();

        foreach (
            [
                ['symbol' => 'IN1', 'percentage' => '40.0000'],
                ['symbol' => 'IN2', 'percentage' => '30.0000'],
                ['symbol' => 'IN3', 'percentage' => '20.0000'],
                ['symbol' => 'IN4', 'percentage' => '10.0000'],
            ] as $row
        ) {
            $security = $this->createSecurity($row['symbol']);

            $this->createBuyTransaction($portfolio->id, $security->id, 100);

            $this->createMetric($security->id, [
                'aum_change_percentage' => $row['percentage'],
                'aum_change' => 1000000,
            ]);
        }

        foreach (
            [
                ['symbol' => 'OUT1', 'percentage' => '-40.0000'],
                ['symbol' => 'OUT2', 'percentage' => '-30.0000'],
                ['symbol' => 'OUT3', 'percentage' => '-20.0000'],
                ['symbol' => 'OUT4', 'percentage' => '-10.0000'],
            ] as $row
        ) {
            $security = $this->createSecurity($row['symbol']);

            $this->createBuyTransaction($portfolio->id, $security->id, 100);

            $this->createMetric($security->id, [
                'aum_change_percentage' => $row['percentage'],
                'aum_change' => -1000000,
            ]);
        }

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame(4, $data['positive_flow_count']);
        $this->assertSame(4, $data['negative_flow_count']);
        $this->assertCount(3, $data['strongest_inflows']);
        $this->assertCount(3, $data['strongest_outflows']);

        $this->assertSame(
            ['IN1', 'IN2', 'IN3'],
            collect($data['strongest_inflows'])->pluck('symbol')->toArray()
        );

        $this->assertSame(
            ['OUT1', 'OUT2', 'OUT3'],
            collect($data['strongest_outflows'])->pluck('symbol')->toArray()
        );
    }

    public function test_it_excludes_fully_sold_positions(): void
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

        $this->createMetric($heldSecurity->id, [
            'aum_change_percentage' => '15.0000',
            'aum_change' => 15000000,
        ]);

        $this->createMetric($soldSecurity->id, [
            'aum_change_percentage' => '50.0000',
            'aum_change' => 50000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['positive_flow_count']);
        $this->assertSame(['HELD'], $data['affected_securities']);
        $this->assertSame('HELD', $data['strongest_inflows'][0]['symbol']);
    }

    public function test_it_ignores_metrics_that_are_not_thirty_day_range(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'aum_change_percentage' => '100.0000',
            'aum_change' => 100000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_ignores_metrics_with_null_aum_change_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NULL');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $this->createMetric($security->id, [
            'aum_change_percentage' => null,
            'aum_change' => 1000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_data_when_aum_change_is_zero(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('FLAT');

        $this->createBuyTransaction($portfolio->id, $security->id, 100);

        $this->createMetric($security->id, [
            'aum_change_percentage' => '0.0000',
            'aum_change' => 0,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(0, $data['positive_flow_count']);
        $this->assertSame(0, $data['negative_flow_count']);
        $this->assertSame(['FLAT'], $data['affected_securities']);
        $this->assertCount(1, $data['all_rows']);
        $this->assertSame(0.0, $data['all_rows'][0]['aum_change_percentage']);
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

    private function createMetric(int $securityId, array $overrides = []): SecurityMetric
    {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $securityId,
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'start_date' => '2026-04-01',
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
            'start_aum' => 100000000,
            'end_aum' => 110000000,
            'aum_change' => 10000000,
            'aum_change_percentage' => '10.0000',
            'aum_direction_id' => 1,
            'calculated_at' => now(),
        ], $overrides));
    }
}
