<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\DividendSignalsQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DividendSignalsQueryTest extends TestCase
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

    public function test_it_returns_empty_array_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $this->assertSame([], $signals);
    }

    public function test_it_returns_distribution_growth_signal_when_recent_distribution_increased(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.2500',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $distributionGrowthSignal = $signals[0];

        $this->assertSame('Distribution Growth', $distributionGrowthSignal['title']);
        $this->assertSame(['NVII'], $distributionGrowthSignal['affected_etfs']);
        $this->assertStringContainsString('NVII', $distributionGrowthSignal['message']);
        $this->assertStringContainsString('25', $distributionGrowthSignal['observation']);
        $this->assertContains('Higher options premium', $distributionGrowthSignal['possible_causes']);
    }

    public function test_it_returns_no_growth_message_when_recent_distribution_did_not_increase(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'QQQI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.2500',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $distributionGrowthSignal = $signals[0];

        $this->assertSame('Distribution Growth', $distributionGrowthSignal['title']);
        $this->assertSame([], $distributionGrowthSignal['affected_etfs']);
        $this->assertSame(
            'No recent distribution growth was detected across current holdings.',
            $distributionGrowthSignal['message']
        );
    }

    public function test_it_returns_weekly_cadence_watch_for_expected_undeclared_weekly_events(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'XDTE',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.3000',
            'ex_dividend_date' => '2026-05-13',
            'payment_date' => '2026-05-15',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $weeklyCadenceSignal = $signals[1];

        $this->assertSame('Weekly Cadence Watch', $weeklyCadenceSignal['title']);
        $this->assertSame(['XDTE'], $weeklyCadenceSignal['affected_etfs']);
        $this->assertSame(
            'Some weekly payer events are expected but not yet declared. Amounts remain TBD until confirmed.',
            $weeklyCadenceSignal['message']
        );
    }

    public function test_weekly_cadence_watch_does_not_flag_weekly_holding_with_future_declared_dividend(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'QDTE',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.3000',
            'ex_dividend_date' => '2026-05-27',
            'payment_date' => '2026-05-29',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $weeklyCadenceSignal = $signals[1];

        $this->assertSame('Weekly Cadence Watch', $weeklyCadenceSignal['title']);
        $this->assertSame(['QDTE'], $weeklyCadenceSignal['affected_etfs']);
        $this->assertSame(
            'Weekly dividend holdings have declared upcoming dividend events or lack enough history for cadence estimates.',
            $weeklyCadenceSignal['message']
        );
    }

    public function test_weekly_cadence_watch_reports_no_weekly_holdings_when_only_monthly_holdings_exist(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-03',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $weeklyCadenceSignal = $signals[1];

        $this->assertSame('Weekly Cadence Watch', $weeklyCadenceSignal['title']);
        $this->assertSame([], $weeklyCadenceSignal['affected_etfs']);
        $this->assertSame(
            'No weekly dividend holdings were detected in this portfolio.',
            $weeklyCadenceSignal['message']
        );
    }

    public function test_it_returns_income_stability_signal_when_income_spread_is_healthy(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.1000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $incomeStabilitySignal = $signals[2];

        $this->assertSame('Income Stability', $incomeStabilitySignal['title']);
        $this->assertSame(['Portfolio'], $incomeStabilitySignal['affected_etfs']);
        $this->assertSame(
            'Portfolio income variance remains within healthy expected ranges.',
            $incomeStabilitySignal['message']
        );
    }

    public function test_it_returns_income_variation_signal_when_income_spread_is_high(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'AMDY',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $incomeStabilitySignal = $signals[2];

        $this->assertSame('Income Stability', $incomeStabilitySignal['title']);
        $this->assertSame(
            'Portfolio income has shown noticeable variation across recent dividend cycles.',
            $incomeStabilitySignal['message']
        );
    }

    public function test_it_returns_more_history_needed_when_less_than_two_months_have_income(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'NEWF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $incomeStabilitySignal = $signals[2];

        $this->assertSame('Income Stability', $incomeStabilitySignal['title']);
        $this->assertSame(
            'More dividend history is needed to evaluate portfolio income stability.',
            $incomeStabilitySignal['message']
        );
    }

    public function test_it_excludes_fully_sold_positions_from_signals(): void
    {
        $portfolio = $this->createPortfolio();

        $etf = Etf::factory()->create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $signals = (new DividendSignalsQuery())->getData($portfolio->id);

        $this->assertSame([], $signals);
    }

    private function createPortfolio(): Portfolio
    {
        $user = User::factory()->create();

        return Portfolio::factory()->create([
            'user_id' => $user->id,
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
}
