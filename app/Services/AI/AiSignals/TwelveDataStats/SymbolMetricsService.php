<?php

namespace App\Services\AI\AiSignals\TwelveDataStats;

class SymbolMetricsService
{
    public function getData(
        array $history
    ): array {

        return [

            'one_month_return' => $this->calculateReturn(
                $history,
                21
            ),

            'three_month_return' => $this->calculateReturn(
                $history,
                63
            ),

            'six_month_return' => $this->calculateReturn(
                $history,
                126
            ),

            'fifty_day_ma' => $this->calculateMovingAverage(
                $history,
                50
            ),

            'two_hundred_day_ma' => $this->calculateMovingAverage(
                $history,
                200
            ),

        ];
    }

    protected function calculateReturn(
        array $history,
        int $days
    ): ?float {

        if (
            ! isset($history[0]['close']) ||
            ! isset($history[$days]['close'])
        ) {
            return null;
        }

        $currentPrice =
            (float) $history[0]['close'];

        $historicalPrice =
            (float) $history[$days]['close'];

        if (
            $historicalPrice <= 0
        ) {
            return null;
        }

        return round(

            (
                ($currentPrice - $historicalPrice)
                / $historicalPrice
            ) * 100,

            2

        );
    }

    protected function calculateMovingAverage(
        array $history,
        int $days
    ): ?float {

        if (
            count($history) < $days
        ) {
            return null;
        }

        $prices = array_slice(
            array_column(
                $history,
                'close'
            ),
            0,
            $days
        );

        $prices = array_map(
            'floatval',
            $prices
        );

        return round(

            array_sum($prices)
            / count($prices),

            2

        );
    }
}
