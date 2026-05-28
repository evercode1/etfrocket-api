<?php

namespace App\Services\PortfolioTransactions;

use App\Models\PortfolioTransaction;

class UpdatePortfolioTransactionService
{
    public function update(int $userId, int $transactionId, array $data): PortfolioTransaction
    {
        $transaction = PortfolioTransaction::query()
            ->select('portfolio_transactions.*')
            ->join('portfolios', 'portfolio_transactions.portfolio_id', '=', 'portfolios.id')
            ->where('portfolio_transactions.id', $transactionId)
            ->where('portfolios.user_id', $userId)
            ->firstOrFail();

        if (array_key_exists('security_id', $data)) {
            $transaction->security_id = $data['security_id'];
        }

        if (array_key_exists('transaction_type_id', $data)) {
            $transaction->transaction_type_id = $data['transaction_type_id'];
        }

        if (array_key_exists('shares', $data)) {
            $transaction->shares = $data['shares'];
        }

        if (array_key_exists('price_per_share', $data)) {
            $transaction->price_per_share = $data['price_per_share'];
        }

        if (array_key_exists('transaction_date', $data)) {
            $transaction->transaction_date = $data['transaction_date'];
        }

        $transaction->save();

        return $transaction->refresh();
    }
}
