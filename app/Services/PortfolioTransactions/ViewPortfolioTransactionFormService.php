<?php

namespace App\Services\PortfolioTransactions;

use App\Models\PortfolioTransaction;

class ViewPortfolioTransactionFormService
{
    public function getData(int $userId, int $transactionId): PortfolioTransaction
    {
        return PortfolioTransaction::query()
            ->select('portfolio_transactions.*')
            ->join('portfolios', 'portfolio_transactions.portfolio_id', '=', 'portfolios.id')
            ->where('portfolio_transactions.id', $transactionId)
            ->where('portfolios.user_id', $userId)
            ->firstOrFail();
    }
}
