<?php

namespace App\Services\PortfolioTransactions;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;

class CreatePortfolioTransactionService
{
    public function create(int $userId, int $portfolioId, array $data): PortfolioTransaction
    {
        Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        return PortfolioTransaction::create([
            'portfolio_id' => $portfolioId,
            'security_id' => $data['security_id'],
            'transaction_type_id' => $data['transaction_type_id'],
            'shares' => $data['shares'],
            'price_per_share' => $data['price_per_share'],
            'transaction_date' => $data['transaction_date'],
        ]);
    }
}
