<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\DividendHistoryQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DividendHistoryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function test_it_returns_paid_dividend_history_for_current_portfolio_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklyEtf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $monthlyEtf = Etf::factory()->create([
            'symbol' => 'JEPI',
            'fund_name' => 'JEPI Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $weeklyEtf->id, 10);
        $this->createBuyTransaction($portfolio->id, $monthlyEtf->id, 5);

        EtfDividendHistory::factory()->create([
            'etf_id' => $weeklyEtf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $monthlyEtf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-05',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(2, $result->total());

        $rows = collect($result->items());

        $this->assertSame('NVII', $rows[0]->symbol);
        $this->assertSame('2026-05-15', $rows[0]->ex_dividend_date);
        $this->assertSame('2026-05-16', $rows[0]->payment_date);
        $this->assertSame('0.5000', (string) $rows[0]->dividend_amount);

        $this->assertSame('JEPI', $rows[1]->symbol);
        $this->assertSame('2026-05-01', $rows[1]->ex_dividend_date);
        $this->assertSame('2026-05-05', $rows[1]->payment_date);
        $this->assertSame('1.0000', (string) $rows[1]->dividend_amount);
    }

    public function test_it_excludes_unpaid_dividend_history(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => null,
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(0, $result->total());
    }

    public function test_it_excludes_dividends_for_etfs_not_currently_held(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $heldEtf = Etf::factory()->create([
            'symbol' => 'HELD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $notHeldEtf = Etf::factory()->create([
            'symbol' => 'NOPE',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $heldEtf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $heldEtf->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $notHeldEtf->id,
            'dividend_amount' => '9.9900',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('HELD', $result->items()[0]->symbol);
    }

    public function test_it_excludes_fully_sold_positions(): void
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
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(0, $result->total());
    }

    public function test_it_filters_by_symbol(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

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

        $this->createBuyTransaction($portfolio->id, $nvi->id, 10);
        $this->createBuyTransaction($portfolio->id, $jepi->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $nvi->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $jepi->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request([
                'symbol' => 'NV',
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('NVII', $result->items()[0]->symbol);
    }

    public function test_it_filters_by_frequency_id(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklyEtf = Etf::factory()->create([
            'symbol' => 'WEEK',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $monthlyEtf = Etf::factory()->create([
            'symbol' => 'MONTH',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        $this->createBuyTransaction($portfolio->id, $weeklyEtf->id, 10);
        $this->createBuyTransaction($portfolio->id, $monthlyEtf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $weeklyEtf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $monthlyEtf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request([
                'frequency_id' => 4,
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('MONTH', $result->items()[0]->symbol);
    }

    public function test_it_filters_by_ex_dividend_date_range(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $this->createBuyTransaction($portfolio->id, $etf->id, 10);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        $result = (new DividendHistoryQuery())->getData(
            new Request([
                'date_from' => '2026-05-01',
                'date_to' => '2026-05-31',
                'per_page' => 25,
            ]),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('2026-05-15', $result->items()[0]->ex_dividend_date);
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $this->expectException(ModelNotFoundException::class);

        (new DividendHistoryQuery())->getData(
            new Request(['per_page' => 25]),
            $user->id,
            $portfolio->id
        );
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
