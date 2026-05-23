<?php

namespace App\Services\BackTesting;

use App\Queries\BackTesting\GetBackTestDividendHistoryQuery;
use App\Queries\BackTesting\GetBackTestPriceHistoryQuery;
use Carbon\Carbon;

class BackTestingService
{
    public function __construct(

        private GetBackTestPriceHistoryQuery
        $priceHistoryQuery,

        private GetBackTestDividendHistoryQuery
        $dividendHistoryQuery,

        private GenerateBackTestAnalyticsService
        $analyticsService,

    ) {}

    public function getData(
        int $etfId,
        string $startDate,
        string $endDate,
        float $initialInvestment,
        float $monthlyContribution = 0,
        float $dripPercentage = 100,
    ): array {

        $prices =
            $this->priceHistoryQuery
            ->getData(

                etfId: $etfId,

                startDate: $startDate,

                endDate: $endDate,

            );

        $dividends =
            $this->dividendHistoryQuery
            ->getData(

                etfId: $etfId,

                startDate: $startDate,

                endDate: $endDate,

            );

        if (empty($prices)) {

            return [

                'chart_rows' => [],

                'summary' => [

                    'final_value' => 0,

                    'total_contributions' => 0,

                    'total_dividends' => 0,

                    'ending_shares' => 0,

                ],

                'analytics' => [

                    'cagr' => 0,

                    'max_drawdown' => 0,

                    'total_return_percentage' => 0,

                ],

            ];
        }

        $shares = 0;

        $cashDividends = 0;

        $totalContributions =
            $initialInvestment;

        $dividendMap =
            collect($dividends)

            ->keyBy('date');

        $firstPrice =
            $prices[0]['price'];

        $shares =
            $initialInvestment /
            $firstPrice;

        $chartRows = [];

        $lastContributionMonth =
            null;

        foreach ($prices as $row) {

            $date =
                Carbon::parse(
                    $row['date']
                );

            $price =
                $row['price'];

            $monthKey =
                $date->format('Y-m');

            if (
                $monthlyContribution > 0 &&
                $monthKey !==
                $lastContributionMonth
            ) {

                $shares +=
                    $monthlyContribution /
                    $price;

                $totalContributions +=
                    $monthlyContribution;

                $lastContributionMonth =
                    $monthKey;
            }

            $dividend =

                collect($dividends)

                ->where('date', $row['date'])

                ->sum('dividend');

            if ($dividend > 0) {

                $dividendIncome =
                    $shares *
                    $dividend;

                $reinvestedAmount =
                    $dividendIncome *
                    ($dripPercentage / 100);

                $cashPortion =
                    $dividendIncome -
                    $reinvestedAmount;

                $cashDividends +=
                    $cashPortion;

                if (
                    $reinvestedAmount > 0
                ) {

                    $shares +=
                        $reinvestedAmount /
                        $price;
                }
            }

            $portfolioValue =
                $shares * $price;

            $chartRows[] = [

                'date' =>
                $row['date'],

                'portfolio_value' =>
                round(
                    $portfolioValue,
                    2
                ),

                'shares' =>
                round(
                    $shares,
                    4
                ),

                'price' =>
                round(
                    $price,
                    2
                ),

                'income' =>
                round(
                    $cashDividends,
                    2
                ),

            ];
        }

        $finalValue =
            end($chartRows)['portfolio_value'];

        $analytics =
            $this->analyticsService
            ->getData(

                chartRows: $chartRows,

                initialInvestment: $initialInvestment,

            );

        return [

            'chart_rows' => $chartRows,

            'summary' => [

                'final_value' =>
                round(
                    $finalValue,
                    2
                ),

                'total_contributions' =>
                round(
                    $totalContributions,
                    2
                ),

                'total_dividends' =>
                round(
                    $cashDividends,
                    2
                ),

                'ending_shares' =>
                round(
                    $shares,
                    4
                ),

            ],

            'analytics' => $analytics,

        ];
    }
}
