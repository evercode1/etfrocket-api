<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
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
        $this->assertSame([], $data['affected_etfs']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_empty_signal_when_holdings_have_no_aum_metrics(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $data['range_type_id']);
        $this->assertSame(0, $data['positive_flow_count']);
        $this->assertSame(0, $data['negative_flow_count']);
        $this->assertSame([], $data['strongest_inflows']);
        $this->assertSame([], $data['strongest_outflows']);
        $this->assertSame([], $data['affected_etfs']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_aum_growth_signal_data_for_current_holdings(): void
    {
        $portfolio = $this->createPortfolio();

        $inflowEtf = $this->createEtf('INFL');
        $outflowEtf = $this->createEtf('OUTF');

        $this->createBuyTransaction($portfolio->id, $inflowEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $outflowEtf->id, 50);

        $this->createMetric($inflowEtf->id, [
            'start_aum' => 100000000,
            'end_aum' => 125000000,
            'aum_change' => 25000000,
            'aum_change_percentage' => '25.0000',
            'aum_direction_id' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-01',
        ]);

        $this->createMetric($outflowEtf->id, [
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
        $this->assertSame(['INFL', 'OUTF'], $data['affected_etfs']);
        $this->assertCount(2, $data['all_rows']);

        $inflow = $data['strongest_inflows'][0];

        $this->assertSame($inflowEtf->id, $inflow['etf_id']);
        $this->assertSame('INFL', $inflow['symbol']);
        $this->assertSame('INFL Test ETF', $inflow['fund_name']);
        $this->assertSame(100.0, $inflow['shares']);
        $this->assertSame(100000000, $inflow['start_aum']);
        $this->assertSame(125000000, $inflow['end_aum']);
        $this->assertSame(25000000, $inflow['aum_change']);
        $this->assertSame(25.0, $inflow['aum_change_percentage']);
        $this->assertSame(1, $inflow['aum_direction_id']);
        $this->assertSame('2026-04-01', $inflow['start_date']);
        $this->assertSame('2026-05-01', $inflow['end_date']);

        $outflow = $data['strongest_outflows'][0];

        $this->assertSame($outflowEtf->id, $outflow['etf_id']);
        $this->assertSame('OUTF', $outflow['symbol']);
        $this->assertSame(-10.0, $outflow['aum_change_percentage']);
        $this->assertSame(-20000000, $outflow['aum_change']);
    }

    public function test_it_sorts_strongest_inflows_by_highest_aum_growth_percentage(): void
    {
        $portfolio = $this->createPortfolio();

        $smallGrowthEtf = $this->createEtf('SMOL');
        $largeGrowthEtf = $this->createEtf('BIGG');

        $this->createBuyTransaction($portfolio->id, $smallGrowthEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $largeGrowthEtf->id, 100);

        $this->createMetric($smallGrowthEtf->id, [
            'aum_change_percentage' => '5.0000',
            'aum_change' => 5000000,
        ]);

        $this->createMetric($largeGrowthEtf->id, [
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

        $smallDeclineEtf = $this->createEtf('SMOL');
        $largeDeclineEtf = $this->createEtf('BIGD');

        $this->createBuyTransaction($portfolio->id, $smallDeclineEtf->id, 100);
        $this->createBuyTransaction($portfolio->id, $largeDeclineEtf->id, 100);

        $this->createMetric($smallDeclineEtf->id, [
            'aum_change_percentage' => '-4.0000',
            'aum_change' => -4000000,
        ]);

        $this->createMetric($largeDeclineEtf->id, [
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
            $etf = $this->createEtf($row['symbol']);

            $this->createBuyTransaction($portfolio->id, $etf->id, 100);

            $this->createMetric($etf->id, [
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
            $etf = $this->createEtf($row['symbol']);

            $this->createBuyTransaction($portfolio->id, $etf->id, 100);

            $this->createMetric($etf->id, [
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

        $this->createMetric($heldEtf->id, [
            'aum_change_percentage' => '15.0000',
            'aum_change' => 15000000,
        ]);

        $this->createMetric($soldEtf->id, [
            'aum_change_percentage' => '50.0000',
            'aum_change' => 50000000,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_data']);
        $this->assertSame(1, $data['positive_flow_count']);
        $this->assertSame(['HELD'], $data['affected_etfs']);
        $this->assertSame('HELD', $data['strongest_inflows'][0]['symbol']);
    }

    public function test_it_ignores_metrics_that_are_not_thirty_day_range(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
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

        $etf = $this->createEtf('NULL');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, [
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

        $etf = $this->createEtf('FLAT');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, [
            'aum_change_percentage' => '0.0000',
            'aum_change' => 0,
        ]);

        $data = app(PortfolioAumGrowthSignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertTrue($data['has_data']);
        $this->assertSame(0, $data['positive_flow_count']);
        $this->assertSame(0, $data['negative_flow_count']);
        $this->assertSame(['FLAT'], $data['affected_etfs']);
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

    private function createMetric(int $etfId, array $overrides = []): EtfMetric
    {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etfId,
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
