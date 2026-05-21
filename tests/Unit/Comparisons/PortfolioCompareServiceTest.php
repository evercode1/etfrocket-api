<?php

namespace Tests\Unit\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\Comparisons\PortfolioCompareService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioCompareServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_comparison_data(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 10, 25);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-01',
            'close_price' => '25.0000',
            'volume' => 1000,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-20',
            'close_price' => '30.0000',
            'volume' => 1000,
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'price_change_percentage' => '20.0000',
            'aum_change_percentage' => '12.0000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
            'total_return_percentage' => '18.0000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
            'total_return_percentage' => '25.0000',
        ]);

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id,
            [
                'metric' => 'price',
                'range' => '30d',
            ]
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame('Main Portfolio', $data['portfolio']['name']);

        $this->assertSame('price', $data['selected']['metric']);
        $this->assertSame('30d', $data['selected']['range']);

        $this->assertSame(1, $data['summary']['compared_etfs_count']);
        $this->assertSame('NVII', $data['summary']['best_total_return_symbol']);
        $this->assertSame(18.0, $data['summary']['best_total_return_percentage']);
        $this->assertSame('NVII', $data['summary']['strongest_nav_symbol']);
        $this->assertSame(-2.0, $data['summary']['strongest_nav_change_percentage']);

        $this->assertCount(1, $data['table_rows']);

        $row = $data['table_rows'][0];

        $this->assertSame($etf->id, $row['etf_id']);
        $this->assertSame('NVII', $row['symbol']);
        $this->assertSame(10.0, $row['shares']);
        $this->assertSame(250.0, $row['cost_basis']);
        $this->assertSame(30.0, $row['latest_price']);
        $this->assertSame(300.0, $row['market_value']);
        $this->assertSame(20.0, $row['price_change_percentage_30_day']);
        $this->assertSame(12.0, $row['aum_change_percentage_30_day']);
        $this->assertSame(-2.0, $row['nav_change_percentage_max']);
        $this->assertSame(18.0, $row['total_return_percentage_90_day']);
        $this->assertSame('Stable', $row['nav_health']);

        $this->assertCount(2, $data['chart_rows']);
        $this->assertSame('May 01', $data['chart_rows'][0]['date']);
        $this->assertSame(25.0, $data['chart_rows'][0]['NVII']);
        $this->assertSame('May 20', $data['chart_rows'][1]['date']);
        $this->assertSame(30.0, $data['chart_rows'][1]['NVII']);
    }

    public function test_it_returns_empty_payload_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Empty Portfolio');

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame(0, $data['summary']['compared_etfs_count']);
        $this->assertSame([], $data['table_rows']);
        $this->assertSame([], $data['chart_rows']);
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $this->createPortfolio($otherUser->id, 'Other Portfolio');

        $this->expectException(ModelNotFoundException::class);

        app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id
        );
    }

    private function createPortfolio(int $userId, string $name): Portfolio
    {
        return Portfolio::factory()->create([
            'user_id' => $userId,
            'portfolio_name' => $name,
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
        float $shares,
        float $price
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'etf_id' => $etfId,
            'transaction_type_id' => 1,
            'shares' => $shares,
            'price_per_share' => $price,
            'transaction_date' => '2026-01-01',
        ]);
    }

    private function createMetric(
        int $etfId,
        int $rangeTypeId,
        array $overrides = []
    ): EtfMetric {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etfId,
            'performance_range_type_id' => $rangeTypeId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-20',
            'price_change_percentage' => '0.0000',
            'aum_change_percentage' => '0.0000',
            'nav_erosion_percentage' => '0.0000',
            'total_return_percentage' => '0.0000',
            'dividends_paid' => '0.0000',
            'dividend_count' => 0,
        ], $overrides));
    }
}
