<?php

namespace App\Services\AI\AiSignals\TwelveDataStats;

use Illuminate\Support\Facades\Cache;

class TwelveDataStatsService
{
    protected TwelveDataClientService $twelveDataClientService;

    protected SymbolMetricsService $symbolMetricsService;

    public function __construct(

        TwelveDataClientService $twelveDataClientService,

        SymbolMetricsService $symbolMetricsService

    ) {

        $this->twelveDataClientService =

            $twelveDataClientService;

        $this->symbolMetricsService =

            $symbolMetricsService;

    }

    public function getData(): array
    {
        return Cache::remember(

            'ai_signal_twelve_data_stats',

            now()->addHours(4),

            function () {

                return [

                    'generated_at' => now()->toDateTimeString(),

                    'indexes' => $this->buildCategory(
                        config('ai-signals.indexes', [])
                    ),

                    'sectors' => $this->buildCategory(
                        config('ai-signals.sectors', [])
                    ),

                    'bonds' => $this->buildCategory(
                        config('ai-signals.bonds', [])
                    ),

                    'leadership' => $this->buildCategory(
                        config('ai-signals.leadership', [])
                    ),

                ];
            }

        );
    }

    /**
     * Build a telemetry category from configuration.
     *
     * Example:
     *
     * [
     *     'SPY' => 'S&P 500',
     *     'QQQ' => 'Nasdaq 100',
     * ]
     */
    protected function buildCategory(
        array $symbols
    ): array {

        $results = [];

        foreach (
            $symbols as $symbol => $name
        ) {

            $results[$symbol] = [

                'name' => $name,

                'metrics' => $this->getSymbolData(
                    $symbol
                ),

            ];
        }

        return $results;
    }

    /**
     * Build a normalized telemetry payload for a symbol.
     *
     * The goal of this method is to hide all Twelve Data
     * implementation details from the rest of the application.
     *
     * It combines:
     *
     * - Current quote data
     * - Historical price data
     * - Calculated metrics
     *
     * Into a single normalized structure.
     *
     * Example:
     *
     * [
     *     'symbol' => 'SPY',
     *     'price' => 648.52,
     *     'change_percent' => 0.83,
     *     'one_month_return' => 4.21,
     *     'three_month_return' => 8.11,
     *     'six_month_return' => 12.40,
     *     'fifty_day_ma' => 625.12,
     *     'two_hundred_day_ma' => 590.88,
     * ]
     */
    protected function getSymbolData(
        string $symbol
    ): array {

        /**
         * Retrieve current quote information.
         *
         * Example:
         *
         * [
         *     'symbol' => 'SPY',
         *     'price' => 648.52,
         *     'change_percent' => 0.83,
         * ]
         */
        $quote =
            $this->twelveDataClientService
                ->getQuote(
                    $symbol
                );

        /**
         * Retrieve historical daily prices.
         *
         * Example:
         *
         * [
         *     [
         *         'datetime' => '2026-06-02',
         *         'close' => 648.52,
         *     ],
         * ]
         */
        $history =
            $this->twelveDataClientService
                ->getDailyHistory(

                    $symbol,

                    config(
                        'ai-signals.telemetry.history_days',
                        250
                    )

                );

        /**
         * Calculate returns and moving averages
         * from historical price data.
         */
        $metrics =
            $this->symbolMetricsService
                ->getData(
                    $history
                );

        /**
         * Merge quote data and calculated metrics
         * into a single telemetry payload.
         */
        return [

            'symbol' => $symbol,

            'price' => $quote['price'],

            'change_percent' => $quote['change_percent'],

            ...$metrics,

        ];
    }
}
