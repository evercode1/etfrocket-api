<?php

namespace Tests\Unit\Transactions;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\User;
use App\Services\PortfolioTransactions\ListPortfolioTransactionsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_paginated_portfolio_transactions_ordered_by_transaction_date_ascending_by_default(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $security = Security::factory()->create();

        $oldest = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-10',
        ]);

        $newer = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-12',
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            new Request,
            $user->id,
            $portfolio->id
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);

        $this->assertSame(
            [
                $oldest->id,
                $newer->id,
            ],
            collect($results->items())->pluck('id')->toArray()
        );
    }

    public function test_it_can_sort_transactions_by_transaction_date_descending(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $security = Security::factory()->create();

        $oldest = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-10',
        ]);

        $newerFirst = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-12',
        ]);

        $newerSecond = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-12',
        ]);

        $request = new Request([
            'sortBy' => 1,
            'sortOrder' => 'desc',
            'limit' => 10,
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
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

        $schd = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vym = Security::factory()->create([
            'symbol' => 'VYM',
        ]);

        $matchingTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
            'transaction_date' => '2026-05-12',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $vym->id,
            'transaction_date' => '2026-05-13',
        ]);

        $request = new Request([
            'limit' => 10,
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
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

        $security = Security::factory()->create();

        $first = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-13',
        ]);

        $second = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-12',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-11',
        ]);

        $request = new Request([
            'limit' => 2,
            'sortBy' => 1,
            'sortOrder' => 'desc',
        ]);

        $service = new ListPortfolioTransactionsService;

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

    public function test_it_paginates_transactions_when_no_limit_is_provided(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $security = Security::factory()->create();

        PortfolioTransaction::factory()
            ->count(30)
            ->create([
                'portfolio_id' => $portfolio->id,
                'security_id' => $security->id,
                'transaction_date' => '2026-05-12',
            ]);

        $request = new Request([
            'per_page' => 25,
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
            $user->id,
            $portfolio->id
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);

        $this->assertSame(25, $results->perPage());

        $this->assertSame(30, $results->total());

        $this->assertCount(25, $results->items());
    }

    public function test_it_can_sort_transactions_by_etf_symbol(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $vym = Security::factory()->create([
            'symbol' => 'VYM',
        ]);

        $schd = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vymTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $vym->id,
            'transaction_date' => '2026-05-12',
        ]);

        $schdTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
            'transaction_date' => '2026-05-12',
        ]);

        $request = new Request([
            'limit' => 10,
            'sortBy' => 2,
            'sortOrder' => 'asc',
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
            $user->id,
            $portfolio->id
        );

        $this->assertSame(
            [
                $schdTransaction->id,
                $vymTransaction->id,
            ],
            $results->pluck('id')->toArray()
        );
    }

    public function test_it_can_sort_transactions_by_transaction_value(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $security = Security::factory()->create();

        $smallTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'shares' => 5,
            'price_per_share' => 10,
            'transaction_date' => '2026-05-12',
        ]);

        $largeTransaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-05-12',
        ]);

        $request = new Request([
            'limit' => 10,
            'sortBy' => 6,
            'sortOrder' => 'desc',
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
            $user->id,
            $portfolio->id
        );

        $this->assertSame(
            [
                $largeTransaction->id,
                $smallTransaction->id,
            ],
            $results->pluck('id')->toArray()
        );

        $this->assertSame(
            '200.00000000',
            (string) $results->first()->transaction_value
        );
    }

    public function test_it_does_not_return_transactions_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        $service = new ListPortfolioTransactionsService;

        $service->getData(
            new Request,
            $user->id,
            $otherPortfolio->id
        );
    }

    public function test_it_returns_etf_symbol_with_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-15',
        ]);

        $request = new Request([
            'limit' => 10,
        ]);

        $service = new ListPortfolioTransactionsService;

        $results = $service->getData(
            $request,
            $user->id,
            $portfolio->id
        );

        $this->assertCount(1, $results);

        $this->assertSame(
            'SCHD',
            $results->first()->symbol
        );
    }
}
