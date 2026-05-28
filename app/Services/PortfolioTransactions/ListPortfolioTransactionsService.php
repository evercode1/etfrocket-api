<?php

namespace App\Services\PortfolioTransactions;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Utilities\SortBy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ListPortfolioTransactionsService
{
    private array $columns = [
        'portfolio_transactions.transaction_date',
        'securities.symbol',
        'portfolio_transactions.transaction_type_id',
        'portfolio_transactions.shares',
        'portfolio_transactions.price_per_share',
        'transaction_value',
        'portfolio_transactions.id',
    ];

    public function getData(
        Request $request,
        int $userId,
        int $portfolioId,
        ?int $securityId = null
    ): Collection|LengthAwarePaginator {

        Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $this->columns);

        $query = PortfolioTransaction::where('portfolio_transactions.portfolio_id', $portfolioId)
            ->leftJoin('securities', 'portfolio_transactions.security_id', '=', 'securities.id')
            ->select([
                'portfolio_transactions.*',
                'securities.symbol',
            ])
            ->selectRaw('(portfolio_transactions.shares * portfolio_transactions.price_per_share) as transaction_value');

        if ($securityId) {
            $query->where('portfolio_transactions.security_id', $securityId);
        }

        $query->orderBy($sortBy, $sortOrder)
            ->orderByDesc('portfolio_transactions.id');

        if ($request->filled('limit')) {
            return $query->limit((int) $request->limit)->get();
        }

        $perPage = (int) $request->input('per_page', 25);

        return $query->paginate($perPage);
    }
}
