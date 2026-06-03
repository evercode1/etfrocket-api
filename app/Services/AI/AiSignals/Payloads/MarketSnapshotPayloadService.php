<?php

namespace App\Services\AI\AiSignals\Payloads;

use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataStatsService;

class MarketSnapshotPayloadService
{
    protected TwelveDataStatsService $twelveDataStatsService;

    public function __construct(
        TwelveDataStatsService $twelveDataStatsService
    ) {

        $this->twelveDataStatsService =
            $twelveDataStatsService;
    }

    /**
     * Build a structured payload for the
     * Market Snapshot AI signal.
     *
     * This payload is intentionally AI-friendly.
     * We derive rankings and leadership data
     * here so the model can focus on analysis
     * instead of calculations.
     */
    public function getData(): array
    {
        $telemetry =
            $this->twelveDataStatsService
                ->getData();

        return [

            'generated_at' => $telemetry['generated_at'],

            'indexes' => $telemetry['indexes'],

            'leadership' => $telemetry['leadership'],

            'top_sectors' => $this->getTopSectors(
                $telemetry['sectors']
            ),

            'bottom_sectors' => $this->getBottomSectors(
                $telemetry['sectors']
            ),

            'best_index' => $this->getBestIndex(
                $telemetry['indexes']
            ),

            'worst_index' => $this->getWorstIndex(
                $telemetry['indexes']
            ),

        ];
    }

    protected function getTopSectors(
        array $sectors
    ): array {

        return collect($sectors)

            ->sortByDesc(function (
                array $sector
            ) {

                return $sector['metrics']['one_month_return']
                    ?? -9999;
            })

            ->take(3)

            ->map(function (
                array $sector
            ) {

                return [

                    'name' => $sector['name'],

                    'one_month_return' => $sector['metrics']['one_month_return'],

                ];
            })

            ->values()

            ->toArray();
    }

    protected function getBottomSectors(
        array $sectors
    ): array {

        return collect($sectors)

            ->sortBy(function (
                array $sector
            ) {

                return $sector['metrics']['one_month_return']
                    ?? 9999;
            })

            ->take(3)

            ->map(function (
                array $sector
            ) {

                return [

                    'name' => $sector['name'],

                    'one_month_return' => $sector['metrics']['one_month_return'],

                ];
            })

            ->values()

            ->toArray();
    }

    protected function getBestIndex(
        array $indexes
    ): ?array {

        return collect($indexes)

            ->sortByDesc(function (
                array $index
            ) {

                return $index['metrics']['one_month_return']
                    ?? -9999;
            })

            ->map(function (
                array $index
            ) {

                return [

                    'name' => $index['name'],

                    'one_month_return' => $index['metrics']['one_month_return'],

                ];
            })

            ->first();
    }

    protected function getWorstIndex(
        array $indexes
    ): ?array {

        return collect($indexes)

            ->sortBy(function (
                array $index
            ) {

                return $index['metrics']['one_month_return']
                    ?? 9999;
            })

            ->map(function (
                array $index
            ) {

                return [

                    'name' => $index['name'],

                    'one_month_return' => $index['metrics']['one_month_return'],

                ];
            })

            ->first();
    }
}
