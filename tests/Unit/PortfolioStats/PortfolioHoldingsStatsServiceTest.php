<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioHoldingsStatsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_empty_collection_when_portfolio_has_no_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertTrue($holdings->isEmpty());
    }

    public function test_it_returns_current_holdings_for_buy_transactions(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $etf->id, 1, 5, 30);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertCount(1, $holdings);

        $holding = $holdings->first();

        $this->assertSame($etf->id, $holding['etf_id']);
        $this->assertSame('NVII', $holding['symbol']);
        $this->assertSame('NVII Test ETF', $holding['fund_name']);
        $this->assertSame(2, $holding['distribution_frequency_id']);
        $this->assertSame(15.0, $holding['shares']);
        $this->assertSame(400.0, $holding['cost_basis']);
    }

    public function test_it_groups_multiple_etfs_separately(): void
    {
        $portfolio = $this->createPortfolio();

        $nvi = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $jepi = Etf::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $this->createTransaction($portfolio->id, $nvi->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $jepi->id, 1, 5, 50);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertCount(2, $holdings);

        $symbols = $holdings->pluck('symbol')->sort()->values()->toArray();

        $this->assertSame(['JEPI', 'NVII'], $symbols);
    }

    public function test_it_reduces_shares_and_cost_basis_for_sell_transactions(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'AMDY',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $etf->id, 2, 4, 30);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertCount(1, $holdings);

        $holding = $holdings->first();

        $this->assertSame(6.0, $holding['shares']);
        $this->assertSame(130.0, $holding['cost_basis']);
    }

    public function test_it_excludes_fully_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $etf->id, 2, 10, 30);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertTrue($holdings->isEmpty());
    }

    public function test_it_excludes_over_sold_positions(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'OVER',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 5, 25);
        $this->createTransaction($portfolio->id, $etf->id, 2, 10, 30);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertTrue($holdings->isEmpty());
    }

    public function test_it_only_returns_holdings_for_requested_portfolio(): void
    {
        $portfolio = $this->createPortfolio();

        $otherPortfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $otherEtf = Etf::factory()->create([
            'symbol' => 'OTHER',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);
        $this->createTransaction($otherPortfolio->id, $otherEtf->id, 1, 99, 10);

        $holdings = (new PortfolioHoldingsStatsService)->getCurrentHoldings(
            $portfolio->id
        );

        $this->assertCount(1, $holdings);
        $this->assertSame('NVII', $holdings->first()['symbol']);
    }

    public function test_has_current_holdings_returns_true_when_holdings_exist(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 10, 25);

        $result = (new PortfolioHoldingsStatsService)->hasCurrentHoldings(
            $portfolio->id
        );

        $this->assertTrue($result);
    }

    public function test_has_current_holdings_returns_false_when_no_holdings_exist(): void
    {
        $portfolio = $this->createPortfolio();

        $result = (new PortfolioHoldingsStatsService)->hasCurrentHoldings(
            $portfolio->id
        );

        $this->assertFalse($result);
    }

    public function test_get_current_etf_ids_returns_current_holding_ids(): void
    {
        $portfolio = $this->createPortfolio();

        $nvi = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $jepi = Etf::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $sold = Etf::factory()->create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $nvi->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $jepi->id, 1, 5, 50);

        $this->createTransaction($portfolio->id, $sold->id, 1, 10, 25);
        $this->createTransaction($portfolio->id, $sold->id, 2, 10, 30);

        $ids = (new PortfolioHoldingsStatsService)->getCurrentEtfIds(
            $portfolio->id
        );

        sort($ids);

        $expected = [$nvi->id, $jepi->id];

        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function test_it_rounds_shares_and_cost_basis_to_four_decimals(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'ROUND',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createTransaction($portfolio->id, $etf->id, 1, 1.23456, 10.98765);

        $holding = (new PortfolioHoldingsStatsService)
            ->getCurrentHoldings($portfolio->id)
            ->first();

        $this->assertSame(1.2346, $holding['shares']);
        $this->assertSame(13.5654, $holding['cost_basis']);
    }

    private function createPortfolio(): Portfolio
    {
        $user = User::factory()->create();

        return Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
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
}
