<?php

namespace App\Services\PortfolioTransactions;

use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPortfolioTransactionsService
{
    public function export(
        int $userId,
        int $portfolioId,
        ?int $etfId = null
    ): StreamedResponse {
        Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        $query = DB::table('portfolio_transactions')

            ->select([
                'etfs.symbol',
                'transaction_types.slug as transaction_type',
                'portfolio_transactions.shares',
                'portfolio_transactions.price_per_share',
                'portfolio_transactions.transaction_date',
            ])

            ->join('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')

            ->join('transaction_types', 'portfolio_transactions.transaction_type_id', '=', 'transaction_types.id')

            ->where('portfolio_transactions.portfolio_id', $portfolioId)

            ->orderBy('portfolio_transactions.transaction_date')

            ->orderBy('portfolio_transactions.id');

        if ($etfId) {
            $query->where('portfolio_transactions.etf_id', $etfId);
        }

        $transactions = $query->get();

        $filename = 'portfolio-transactions-'.$portfolioId.'.csv';

        return response()->streamDownload(function () use ($transactions) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'symbol',
                'transaction_type',
                'shares',
                'price_per_share',
                'transaction_date',
            ]);

            foreach ($transactions as $transaction) {

                fputcsv($handle, [
                    $transaction->symbol,
                    $transaction->transaction_type,
                    $transaction->shares,
                    $transaction->price_per_share,
                    $transaction->transaction_date,
                ]);
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
