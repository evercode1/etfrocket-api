<?php

namespace App\Queries\MissionControl;

use App\Models\PortfolioTransaction;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Services\PortfolioStats\PortfolioHistoricalStatsCollectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PortfolioFlightPathQuery
{
    private const BUY = 1;

    public function __construct(
        private PortfolioHistoricalStatsCollectionService $historicalStatsService
    ) {}

    public function getData(int $portfolio_id): array
    {
        $cacheKey =

            'portfolio_flight_path_'.

            $portfolio_id;

        return Cache::remember(

            $cacheKey,

            now()->addMinutes(15),

            function () use ($portfolio_id) {

                $transactions = PortfolioTransaction::where(
                    'portfolio_id',
                    $portfolio_id
                )
                    ->where(
                        'transaction_type_id',
                        self::BUY
                    )
                    ->orderBy(
                        'transaction_date'
                    )
                    ->get();

                if ($transactions->isEmpty()) {

                    return [];
                }

                $allTransactions = PortfolioTransaction::where(
                    'portfolio_id',
                    $portfolio_id
                )
                    ->orderBy(
                        'transaction_date'
                    )
                    ->get();

                $securityIds = $allTransactions
                    ->pluck('security_id')
                    ->unique()
                    ->values();

                $allPrices = SecurityPriceHistory::whereIn(
                    'security_id',
                    $securityIds
                )
                    ->orderBy(
                        'price_date'
                    )
                    ->get();

                $allDividends = SecurityDividendHistory::whereIn(
                    'security_id',
                    $securityIds
                )
                    ->orderBy(
                        'ex_dividend_date'
                    )
                    ->get();

                $pricesBySecurity = $allPrices
                    ->groupBy(
                        'security_id'
                    );

                $dividendsBySecurity = $allDividends
                    ->groupBy(
                        'security_id'
                    );

                $startDate = Carbon::parse(
                    $transactions->min('transaction_date')
                )->startOfMonth();

                $endDate = now()->startOfMonth();

                $points = [];

                $cursor = $startDate->copy();

                while ($cursor->lte($endDate)) {

                    $monthStart = $cursor
                        ->copy()
                        ->startOfMonth();

                    $monthEnd = $cursor
                        ->copy()
                        ->endOfMonth();

                    if ($monthEnd->gt(now())) {

                        $monthEnd = now();
                    }

                    $points[] = [

                        'date' => $monthStart->format('M Y'),

                        'value' => $this->historicalStatsService
                            ->getPortfolioValueAsOfDate(

                                $allTransactions,

                                $pricesBySecurity,

                                $monthEnd->format('Y-m-d')

                            ),

                        'income' => $this->historicalStatsService
                            ->getDividendIncomeBetweenDates(

                                $allTransactions,

                                $dividendsBySecurity,

                                $monthStart->format('Y-m-d'),

                                $monthEnd->format('Y-m-d')

                            ),

                    ];

                    $cursor->addMonth();
                }

                return $points;
            }

        );
    }
}
