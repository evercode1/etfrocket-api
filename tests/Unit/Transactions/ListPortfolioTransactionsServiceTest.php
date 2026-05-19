<?php

namespace Tests\Unit\Services\PortfolioTransactions;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use App\Services\PortfolioTransactions\ListPortfolioTransactionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListPortfolioTransactionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_transactions_ordered_by_transaction_date_and_id_desc(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $etf = Etf::factory()->create();

        $oldest = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-10',
        ]);

        $newerFirst = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-12',
        ]);

        $newerSecond = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-12',
        ]);

        $service = new ListPortfolioTransactionsService();

        $results = $service->getData(
            new Request(),
            $user->id,
            $portfolio->id
        );

        $this->assertSame(
            [
                $newerSecond->id,
                $newerFirst->id,
                $oldest->id,
            ],
            $results->pluck('id')->toArray()
        );
    }

    public function test_it_can_filter_transactions_by_etf_id(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
        ]);

        $matchingTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_date' => '2026-05-12',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $vym->id,
            'transaction_date' => '2026-05-13',
        ]);

        $service = new ListPortfolioTransactionsService();

        $results = $service->getData(
            new Request(),
            $user->id,
            $portfolio->id,
            $schd->id
        );

        $this->assertCount(1, $results);

        $this->assertSame($matchingTransaction->id, $results->first()->id);
    }

    public function test_it_can_limit_transactions_from_request(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $etf = Etf::factory()->create();

        $first = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-13',
        ]);

        $second = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-12',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_date' => '2026-05-11',
        ]);

        $request = new Request([
            'limit' => 2,
        ]);

        $service = new ListPortfolioTransactionsService();

        $results = $service->getData(
            $request,
            $user->id,
            $portfolio->id
        );

        $this->assertCount(2, $results);

        $this->assertSame(
            [
                $first->id,
                $second->id,
            ],
            $results->pluck('id')->toArray()
        );
    }

    public function test_it_does_not_return_transactions_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $service = new ListPortfolioTransactionsService();

        $service->getData(
            new Request(),
            $user->id,
            $otherPortfolio->id
        );
    }
}
