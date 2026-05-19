<?php

namespace App\Services\PortfolioTransactions;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

class ListPortfolioTransactionsService
{
    public function getData(

        Request $request,
        int $userId,
        int $portfolioId,
        ?int $etfId = null
    ): Collection {

        Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        $query = PortfolioTransaction::where('portfolio_transactions.portfolio_id', $portfolioId)

            ->leftJoin('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')

            ->select([
                'portfolio_transactions.*',
                'etfs.symbol',
            ]);

        if ($etfId) {
            $query->where('portfolio_transactions.etf_id', $etfId);
        }

        $query->orderByDesc('portfolio_transactions.transaction_date')
            ->orderByDesc('portfolio_transactions.id');

        if ($request->filled('limit')) {
            $query->limit((int) $request->limit);
        }

        return $query->get();
    }
}
