<?php

namespace App\Queries\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Services\PortfolioStats\PortfolioHistoricalStatsCollectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DividendHistoryQuery
{
    public function __construct(
        private PortfolioHistoricalStatsCollectionService $historicalStatsService
    ) {}

    public function getData(
        Request $request,
        int $userId,
        int $portfolioId
    ): array {

        Portfolio::where('id', $portfolioId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $transactions = PortfolioTransaction::where(
            'portfolio_id',
            $portfolioId
        )
            ->orderBy('transaction_date')
            ->get();

        $portfolioSecurityIds = PortfolioTransaction::query()
            ->where('portfolio_id', $portfolioId)
            ->distinct()
            ->pluck('security_id');

        $query = DB::table('security_dividend_histories')
            ->join('securities', 'security_dividend_histories.security_id', '=', 'securities.id')
            ->leftJoin('security_details', 'securities.id', '=', 'security_details.security_id')
            ->leftJoin(
                'distribution_frequencies',
                'security_details.distribution_frequency_id',
                '=',
                'distribution_frequencies.id'
            )
            ->whereIn('security_dividend_histories.security_id', $portfolioSecurityIds)
            ->whereNotNull('security_dividend_histories.payment_date')
            ->select([
                'security_dividend_histories.id',
                'security_dividend_histories.security_id',
                'securities.symbol',
                'security_details.security_name',
                'security_details.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
                'security_dividend_histories.dividend_amount',
                'security_dividend_histories.ex_dividend_date',
                'security_dividend_histories.payment_date',
            ]);

        if ($request->filled('symbol')) {
            $query->where(
                'securities.symbol',
                'like',
                '%'.$request->input('symbol').'%'
            );
        }

        if ($request->filled('frequency_id')) {
            $query->where(
                'security_details.distribution_frequency_id',
                (int) $request->input('frequency_id')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'security_dividend_histories.ex_dividend_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'security_dividend_histories.ex_dividend_date',
                '<=',
                $request->input('date_to')
            );
        }

        $rows = $query
            ->orderByDesc('security_dividend_histories.ex_dividend_date')
            ->orderBy('securities.symbol')
            ->get()
            ->map(function ($row) use ($transactions) {
                $sharesOwned = $this->historicalStatsService
                    ->getSharesOwnedAsOfDate(
                        $transactions,
                        (int) $row->security_id,
                        $row->ex_dividend_date
                    );

                $row->shares_owned = round($sharesOwned, 4);

                $row->estimated_payment_amount = round(
                    $sharesOwned * (float) $row->dividend_amount,
                    4
                );

                return $row;
            })
            ->filter(function ($row) {
                return (float) $row->shares_owned > 0;
            })
            ->values();

        $totalPaid = round(
            (float) $rows->sum('estimated_payment_amount'),
            4
        );

        $today = Carbon::today();

        $monthToDatePaid = round(
            (float) $rows
                ->filter(function ($row) use ($today) {
                    return $row->payment_date >= $today->copy()->startOfMonth()->toDateString()
                        && $row->payment_date <= $today->toDateString();
                })
                ->sum('estimated_payment_amount'),
            4
        );

        $lastMonth = Carbon::today()->subMonth();

        $lastMonthPaid = round(
            (float) $rows
                ->filter(function ($row) use ($lastMonth) {
                    return $row->payment_date >= $lastMonth->copy()->startOfMonth()->toDateString()
                        && $row->payment_date <= $lastMonth->copy()->endOfMonth()->toDateString();
                })
                ->sum('estimated_payment_amount'),
            4
        );

        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);

        $paginatedRows = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [
            'total_paid' => $totalPaid,
            'month_to_date_paid' => $monthToDatePaid,
            'last_month_paid' => $lastMonthPaid,
            'dividends' => $paginatedRows,
        ];
    }
}
