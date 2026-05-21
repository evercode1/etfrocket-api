<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\Signals\PortfolioNavStabilitySignalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioNavStabilitySignalServiceTest extends TestCase
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

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertFalse($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame('No Holdings', $data['nav_health']);
        $this->assertNull($data['worst_nav_erosion_percentage']);
        $this->assertSame([], $data['all_rows']);
    }

    public function test_it_returns_unknown_when_holdings_have_no_nav_metrics(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_holdings']);
        $this->assertFalse($data['has_data']);
        $this->assertSame('Unknown', $data['nav_health']);
    }

    public function test_it_returns_stable_nav_signal(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('STBL');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, [
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertTrue($data['has_data']);
        $this->assertSame('Stable', $data['nav_health']);
        $this->assertSame(1, $data['stable_count']);
        $this->assertSame(0, $data['mixed_count']);
        $this->assertSame(0, $data['watch_count']);
        $this->assertSame(-2.0, $data['worst_nav_erosion_percentage']);
        $this->assertSame('STBL', $data['stable_list'][0]['symbol']);
    }

    public function test_it_returns_mixed_nav_signal(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('MIXD');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, [
            'nav_erosion_percentage' => '-5.0000',
        ]);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame('Mixed', $data['nav_health']);
        $this->assertSame(0, $data['stable_count']);
        $this->assertSame(1, $data['mixed_count']);
        $this->assertSame(0, $data['watch_count']);
        $this->assertSame('MIXD', $data['mixed_list'][0]['symbol']);
    }

    public function test_it_returns_watch_nav_signal(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = $this->createEtf('RISK');

        $this->createBuyTransaction($portfolio->id, $etf->id, 100);

        $this->createMetric($etf->id, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame('Watch', $data['nav_health']);
        $this->assertSame(0, $data['stable_count']);
        $this->assertSame(0, $data['mixed_count']);
        $this->assertSame(1, $data['watch_count']);
        $this->assertSame('RISK', $data['watch_list'][0]['symbol']);
    }

    public function test_watch_status_takes_priority_over_mixed_and_stable(): void
    {
        $portfolio = $this->createPortfolio();

        $stable = $this->createEtf('STBL');
        $mixed = $this->createEtf('MIXD');
        $watch = $this->createEtf('RISK');

        $this->createBuyTransaction($portfolio->id, $stable->id, 100);
        $this->createBuyTransaction($portfolio->id, $mixed->id, 100);
        $this->createBuyTransaction($portfolio->id, $watch->id, 100);

        $this->createMetric($stable->id, ['nav_erosion_percentage' => '-1.0000']);
        $this->createMetric($mixed->id, ['nav_erosion_percentage' => '-5.0000']);
        $this->createMetric($watch->id, ['nav_erosion_percentage' => '-12.0000']);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame('Watch', $data['nav_health']);
        $this->assertSame(1, $data['stable_count']);
        $this->assertSame(1, $data['mixed_count']);
        $this->assertSame(1, $data['watch_count']);
        $this->assertSame(-12.0, $data['worst_nav_erosion_percentage']);
    }

    public function test_it_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $held = $this->createEtf('HELD');
        $sold = $this->createEtf('SOLD');

        $this->createBuyTransaction($portfolio->id, $held->id, 100);
        $this->createBuyTransaction($portfolio->id, $sold->id, 100);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $sold->id,
            'transaction_type_id' => 2,
            'shares' => 100,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        $this->createMetric($held->id, ['nav_erosion_percentage' => '-1.0000']);
        $this->createMetric($sold->id, ['nav_erosion_percentage' => '-20.0000']);

        $data = app(PortfolioNavStabilitySignalService::class)
            ->getSignalData($portfolio->id);

        $this->assertSame('Stable', $data['nav_health']);
        $this->assertSame(['HELD'], $data['affected_etfs']);
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
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
            'start_nav' => '10.0000',
            'end_nav' => '9.8000',
            'nav_change' => '-0.2000',
            'nav_erosion_percentage' => '-2.0000',
            'nav_direction_id' => 2,
        ], $overrides));
    }
}
