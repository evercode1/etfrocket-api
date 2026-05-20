<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\UpcomingWeeklyDividendsQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpcomingWeeklyDividendsQueryTest extends TestCase
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

    public function test_it_returns_empty_array_when_no_weekly_holdings_exist(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertSame([], $results);
    }

    public function test_it_returns_declared_weekly_dividend_events(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.2500',
            'ex_dividend_date' => '2026-05-25',
            'payment_date' => '2026-05-27',
            'data_source_id' => 1,
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertCount(1, $results);

        $this->assertSame($etf->id, $results[0]['etf_id']);
        $this->assertSame('NVII', $results[0]['symbol']);
        $this->assertSame('NVII Test ETF', $results[0]['fund_name']);
        $this->assertSame(10.0, $results[0]['shares']);
        $this->assertSame(1.25, $results[0]['distribution_amount']);
        $this->assertSame(12.5, $results[0]['estimated_payment_amount']);
        $this->assertSame('2026-05-25', $results[0]['ex_dividend_date']);
        $this->assertSame('2026-05-27', $results[0]['payment_date']);
        $this->assertSame('Declared', $results[0]['status']);
    }

    public function test_it_returns_expected_weekly_dividend_when_future_dividend_is_not_declared(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'QQQI',
            'fund_name' => 'QQQI Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 40,
            'transaction_date' => '2026-01-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.7500',
            'ex_dividend_date' => '2026-05-13',
            'payment_date' => '2026-05-15',
            'data_source_id' => 1,
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertCount(1, $results);

        $this->assertSame('Expected', $results[0]['status']);
        $this->assertNull($results[0]['distribution_amount']);
        $this->assertNull($results[0]['estimated_payment_amount']);
        $this->assertNull($results[0]['payment_date']);

        // Last ex-date + 7 days
        $this->assertSame('2026-05-20', $results[0]['ex_dividend_date']);
    }

    public function test_it_excludes_non_weekly_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $monthlyEtf = Etf::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $monthlyEtf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $monthlyEtf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-05',
            'data_source_id' => 1,
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertSame([], $results);
    }

    public function test_it_excludes_fully_sold_weekly_positions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

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
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-05',
            'data_source_id' => 1,
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertSame([], $results);
    }

    public function test_it_returns_unknown_status_when_no_dividend_history_exists(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NEWF',
            'fund_name' => 'New Fund ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 3,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        $results = (new UpcomingWeeklyDividendsQuery())->getData($portfolio->id);

        $this->assertCount(1, $results);

        $this->assertSame('Unknown', $results[0]['status']);
        $this->assertNull($results[0]['distribution_amount']);
        $this->assertNull($results[0]['estimated_payment_amount']);
        $this->assertNull($results[0]['ex_dividend_date']);
        $this->assertNull($results[0]['payment_date']);
    }
}
