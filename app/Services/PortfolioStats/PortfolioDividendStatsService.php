<?php

namespace App\Services\PortfolioStats;

use App\Models\SecurityDividendHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PortfolioDividendStatsService
{
    public function loadDividendHistory(
        Collection $holdings
    ): Collection {

        return SecurityDividendHistory::whereIn(

            'security_id',

            $holdings
                ->pluck('security_id')
                ->toArray()

        )->get();
    }

    public function getMonthlyDividendIncome(

        Collection $holdings,

        Carbon $month,

        Collection $dividends

    ): float {

        $monthStart =

            $month
                ->copy()
                ->startOfMonth()
                ->toDateString();

        $monthEnd =

            $month
                ->copy()
                ->endOfMonth()
                ->toDateString();

        $income = 0;

        foreach ($holdings as $holding) {

            $dividendTotal =

                $dividends

                    ->where(

                        'security_id',

                        $holding['security_id']

                    )

                    ->filter(

                        fn ($dividend) => $dividend->ex_dividend_date >=
                        $monthStart

                        &&

                        $dividend->ex_dividend_date <=
                        $monthEnd

                    )

                    ->sum(
                        'dividend_amount'
                    );

            $income +=

                (float) $holding['shares']

                *

                (float) $dividendTotal;
        }

        return round(
            $income,
            4
        );
    }

    public function getAverageRecentMonthlyIncome(

        Collection $holdings,

        int $months = 3,

        ?Collection $dividends = null

    ): float {

        if ($holdings->isEmpty()) {

            return 0;
        }

        $dividends ??=

            $this->loadDividendHistory(
                $holdings
            );

        $latestDividendDate =

            $this->getLatestDividendDate(
                $holdings,
                $dividends
            );

        if (! $latestDividendDate) {

            return 0;
        }

        $latestMonth =

            Carbon::parse(
                $latestDividendDate
            )
                ->startOfMonth();

        $monthlyIncomeRows =

            collect(
                range(
                    0,
                    $months - 1
                )
            )
                ->map(

                    function (

                        int $monthOffset

                    ) use (

                        $holdings,

                        $latestMonth,

                        $dividends

                    ) {

                        $month =

                            $latestMonth
                                ->copy()
                                ->subMonths(
                                    $monthOffset
                                );

                        return

                            $this->getMonthlyDividendIncome(

                                $holdings,

                                $month,

                                $dividends

                            );
                    }

                )
                ->filter(

                    fn (

                        float $income

                    ) => $income > 0

                )
                ->values();

        if (

            $monthlyIncomeRows->isEmpty()

        ) {

            return 0;
        }

        return round(

            (float)

            $monthlyIncomeRows->avg(),

            4

        );
    }

    public function getProjectedMonthlyIncome(

        Collection $holdings,

        ?Collection $dividends = null

    ): float {

        if ($holdings->isEmpty()) {

            return 0;
        }

        $dividends ??=

            $this->loadDividendHistory(
                $holdings
            );

        return $this->getAverageRecentMonthlyIncome(

            $holdings,

            3,

            $dividends

        );
    }

    public function getDividendGrowthPercentage(

        Collection $holdings,

        ?Collection $dividends = null

    ): ?float {

        if ($holdings->isEmpty()) {

            return null;
        }

        $dividends ??=

            $this->loadDividendHistory(
                $holdings
            );

        $latestCompleteMonth =

            Carbon::now()
                ->subMonth()
                ->startOfMonth();

        $previousCompleteMonth =

            $latestCompleteMonth
                ->copy()
                ->subMonth();

        $latestCompleteMonthIncome =

            $this->getMonthlyDividendIncome(

                $holdings,

                $latestCompleteMonth,

                $dividends

            );

        $previousCompleteMonthIncome =

            $this->getMonthlyDividendIncome(

                $holdings,

                $previousCompleteMonth,

                $dividends

            );

        if (

            $previousCompleteMonthIncome <= 0

        ) {

            return null;
        }

        if (

            $latestCompleteMonthIncome <= 0

        ) {

            return null;
        }

        return round(

            (

                (

                    $latestCompleteMonthIncome -

                    $previousCompleteMonthIncome

                )

                /

                $previousCompleteMonthIncome

            ) * 100,

            4

        );
    }

    public function getForwardYieldPercentage(

        Collection $holdings,

        float $projectedMonthlyIncome

    ): ?float {

        $costBasis =

            $holdings->sum(
                'cost_basis'
            );

        if ($costBasis <= 0) {

            return null;
        }

        return round(

            (

                (

                    $projectedMonthlyIncome * 12

                )

                /

                $costBasis

            ) * 100,

            4

        );
    }

    public function getLatestDividendDate(

        Collection $holdings,

        ?Collection $dividends = null

    ): ?string {

        $dividends ??=

            $this->loadDividendHistory(
                $holdings
            );

        $latestDate =

            $dividends
                ->max(
                    'ex_dividend_date'
                );

        if (! $latestDate) {

            return null;
        }

        return Carbon::parse(
            $latestDate
        )->toDateString();
    }

    public function getProjectedIncomeTimeline(

        Collection $holdings,

        int $months = 5,

        float $annualGrowthRate = 0.08,

        ?Collection $dividends = null

    ): array {

        $dividends ??=

            $this->loadDividendHistory(
                $holdings
            );

        $projectedMonthlyIncome =

            $this->getProjectedMonthlyIncome(

                $holdings,

                $dividends

            );

        $monthlyGrowthRate =

            $annualGrowthRate / 12;

        $currentMonth =

            Carbon::now()
                ->startOfMonth();

        return collect(

            range(
                0,
                $months - 1
            )

        )

            ->map(

                function (

                    int $monthOffset

                ) use (

                    $projectedMonthlyIncome,

                    $monthlyGrowthRate,

                    $currentMonth

                ) {

                    $month =

                        $currentMonth
                            ->copy()
                            ->addMonths(
                                $monthOffset
                            );

                    $growthMultiplier =

                        pow(

                            1 +

                            $monthlyGrowthRate,

                            $monthOffset

                        );

                    $projectedIncome =

                        $projectedMonthlyIncome *

                        $growthMultiplier;

                    return [

                        'month' => $month->format(
                            'M'
                        ),

                        'income' => round(
                            $projectedIncome,
                            2
                        ),

                    ];
                }

            )

            ->toArray();
    }
}
