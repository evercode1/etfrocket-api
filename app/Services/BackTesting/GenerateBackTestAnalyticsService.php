<?php

namespace App\Services\BackTesting;

use Carbon\Carbon;

class GenerateBackTestAnalyticsService
{
    public function getData(
        array $chartRows,
        float $initialInvestment
    ): array {

        if (empty($chartRows)) {

            return [

                'cagr' => 0,

                'max_drawdown' => 0,

                'total_return_percentage' => 0,

            ];
        }

        $firstRow = $chartRows[0];

        $lastRow = end($chartRows);

        $startingValue =
            $initialInvestment;

        $endingValue =
            $lastRow['portfolio_value'];

        $startDate =
            Carbon::parse(
                $firstRow['date']
            );

        $endDate =
            Carbon::parse(
                $lastRow['date']
            );

        $years =
            max(
                $startDate->diffInDays($endDate) / 365,
                1 / 365
            );

        $cagr =
            (
                pow(
                    $endingValue / $startingValue,
                    1 / $years
                ) - 1
            ) * 100;

        $peak =
            $chartRows[0]['portfolio_value'];

        $maxDrawdown = 0;

        foreach ($chartRows as $row) {

            $value =
                $row['portfolio_value'];

            if ($value > $peak) {

                $peak = $value;
            }

            $drawdown =
                (
                    ($value - $peak)
                    / $peak
                ) * 100;

            if (
                $drawdown < $maxDrawdown
            ) {

                $maxDrawdown =
                    $drawdown;
            }
        }

        $totalReturnPercentage =
            (
                (
                    $endingValue -
                    $startingValue
                )
                / $startingValue
            ) * 100;

        return [

            'cagr' => round(
                $cagr,
                2
            ),

            'max_drawdown' => round(
                $maxDrawdown,
                2
            ),

            'total_return_percentage' => round(
                $totalReturnPercentage,
                2
            ),

        ];
    }
}
