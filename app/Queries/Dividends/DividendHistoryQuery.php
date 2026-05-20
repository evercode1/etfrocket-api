<?php

namespace App\Queries\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DividendHistoryQuery
{
    private const BUY = 1;
    private const SELL = 2;

    public function getData(
        Request $request,
        int $userId,
        int $portfolioId
    ): LengthAwarePaginator {
        Portfolio::where('id', $portfolioId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $holdingEtfIds = PortfolioTransaction::query()
            ->select('portfolio_transactions.etf_id')
            ->where('portfolio_transactions.portfolio_id', $portfolioId)
            ->groupBy('portfolio_transactions.etf_id')
            ->havingRaw('
                SUM(
                    CASE
                        WHEN portfolio_transactions.transaction_type_id = ? THEN portfolio_transactions.shares
                        WHEN portfolio_transactions.transaction_type_id = ? THEN -portfolio_transactions.shares
                        ELSE 0
                    END
                ) > 0
            ', [self::BUY, self::SELL])
            ->pluck('portfolio_transactions.etf_id');

        $query = DB::table('etf_dividend_histories')
            ->join('etfs', 'etf_dividend_histories.etf_id', '=', 'etfs.id')
            ->leftJoin(
                'distribution_frequencies',
                'etfs.distribution_frequency_id',
                '=',
                'distribution_frequencies.id'
            )
            ->whereIn('etf_dividend_histories.etf_id', $holdingEtfIds)
            ->whereNotNull('etf_dividend_histories.payment_date')
            ->select([
                'etf_dividend_histories.id',
                'etf_dividend_histories.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
                'etfs.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
                'etf_dividend_histories.dividend_amount',
                'etf_dividend_histories.ex_dividend_date',
                'etf_dividend_histories.payment_date',
            ]);

        if ($request->filled('symbol')) {
            $query->where('etfs.symbol', 'like', '%' . $request->input('symbol') . '%');
        }

        if ($request->filled('frequency_id')) {
            $query->where('etfs.distribution_frequency_id', (int) $request->input('frequency_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('etf_dividend_histories.ex_dividend_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('etf_dividend_histories.ex_dividend_date', '<=', $request->input('date_to'));
        }

        $query->orderByDesc('etf_dividend_histories.ex_dividend_date')
            ->orderBy('etfs.symbol');

        $perPage = (int) $request->input('per_page', 25);

        return $query->paginate($perPage);
    }
}
