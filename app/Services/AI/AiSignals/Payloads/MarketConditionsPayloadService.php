<?php

namespace App\Services\AI\AiSignals\Payloads;

use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataStatsService;

class MarketConditionsPayloadService
{
    protected TwelveDataStatsService $twelveDataStatsService;

    public function __construct(
        TwelveDataStatsService $twelveDataStatsService
    ) {

        $this->twelveDataStatsService =
            $twelveDataStatsService;
    }

    /**
     * Build a structured payload describing
     * the current market regime.
     */
    public function getData(): array
    {
        $telemetry =
            $this->twelveDataStatsService
                ->getData();

        return [

            'generated_at' => $telemetry['generated_at'],

            'trend' => $this->resolveTrend(
                $telemetry['indexes']['SPY']['metrics']
            ),

            'momentum' => $this->resolveMomentum(
                $telemetry['indexes']['SPY']['metrics']
            ),

            'growth_vs_defensive' => $this->resolveGrowthVsDefensive(
                $telemetry['sectors']
            ),

            'bond_signal' => $this->resolveBondSignal(
                $telemetry['bonds']
            ),

            'indexes' => $telemetry['indexes'],

            'leadership' => $telemetry['leadership'],

        ];
    }

    protected function resolveTrend(
        array $metrics
    ): string {

        $price =
            $metrics['price'] ?? null;

        $fiftyDay =
            $metrics['fifty_day_ma'] ?? null;

        $twoHundredDay =
            $metrics['two_hundred_day_ma'] ?? null;

        if (
            is_null($price) ||
            is_null($fiftyDay) ||
            is_null($twoHundredDay)
        ) {
            return 'unknown';
        }

        if (
            $price > $fiftyDay &&
            $fiftyDay > $twoHundredDay
        ) {
            return 'bullish';
        }

        if (
            $price < $fiftyDay &&
            $fiftyDay < $twoHundredDay
        ) {
            return 'bearish';
        }

        return 'neutral';
    }

    protected function resolveMomentum(
        array $metrics
    ): string {

        $return =
            $metrics['three_month_return'] ?? null;

        if (
            is_null($return)
        ) {
            return 'unknown';
        }

        if (
            $return >= 10
        ) {
            return 'strong';
        }

        if (
            $return >= 0
        ) {
            return 'moderate';
        }

        return 'weak';
    }

    protected function resolveVixRegime(
        array $metrics
    ): string {

        $vix =
            $metrics['price'] ?? null;

        if (
            is_null($vix)
        ) {
            return 'unknown';
        }

        if (
            $vix < 15
        ) {
            return 'low';
        }

        if (
            $vix < 25
        ) {
            return 'normal';
        }

        if (
            $vix < 35
        ) {
            return 'elevated';
        }

        return 'high';
    }

    protected function resolveGrowthVsDefensive(
        array $sectors
    ): string {

        $growth = collect([

            $sectors['XLK']['metrics']['one_month_return'] ?? 0,

            $sectors['XLY']['metrics']['one_month_return'] ?? 0,

            $sectors['XLC']['metrics']['one_month_return'] ?? 0,

        ])->avg();

        $defensive = collect([

            $sectors['XLU']['metrics']['one_month_return'] ?? 0,

            $sectors['XLP']['metrics']['one_month_return'] ?? 0,

            $sectors['XLV']['metrics']['one_month_return'] ?? 0,

        ])->avg();

        if (
            $growth > $defensive + 2
        ) {
            return 'growth_leading';
        }

        if (
            $defensive > $growth + 2
        ) {
            return 'defensive_leading';
        }

        return 'mixed';
    }

    protected function resolveBondSignal(
        array $bonds
    ): string {

        $tlt =
            $bonds['TLT']['metrics']['one_month_return']
                ?? 0;

        $ief =
            $bonds['IEF']['metrics']['one_month_return']
                ?? 0;

        $average =
            ($tlt + $ief) / 2;

        if (
            $average > 2
        ) {
            return 'risk_off';
        }

        if (
            $average < -2
        ) {
            return 'risk_on';
        }

        return 'neutral';
    }
}
