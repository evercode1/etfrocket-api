<?php

namespace App\Queries\Securities;

use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use Illuminate\Support\Facades\Cache;

class SecurityMetricsQuery
{
    public function getData(int $securityId, int $performanceRangeTypeId): array
    {
        return Cache::remember(

            "security_metrics_{$securityId}_{$performanceRangeTypeId}",

            now()->addHours(6),

            function () use ($securityId, $performanceRangeTypeId) {

                $metric = SecurityMetric::query()
                    ->where(

                        'security_id',

                        $securityId

                    )
                    ->where(

                        'performance_range_type_id',

                        $performanceRangeTypeId

                    )
                    ->first();

                $latestPrice = SecurityPriceHistory::query()

                    ->where(
                        'security_id',
                        $securityId
                    )

                    ->orderByDesc(
                        'price_date'
                    )

                    ->first();

                return [

                    'current_price' => $latestPrice?->close_price,

                    'nav_health' => $this->resolveNavHealth(
                        $metric
                    ),

                    'aum_flow' => $metric?->aum_change_percentage,

                    'total_return' => $metric?->total_return_percentage,

                    'nav_erosion_percentage' => $metric?->nav_erosion_percentage,

                    'price_change_percentage' => $metric?->price_change_percentage,

                ];
            }

        );
    }

    private function resolveNavHealth(
        ?SecurityMetric $metric
    ): string {

        if (! $metric) {

            return 'Unknown';
        }

        $nav = (float) (
            $metric->nav_erosion_percentage ?? 0
        );

        if ($nav >= 0) {

            return 'Stable';
        }

        if ($nav <= -10) {

            return 'Watch';
        }

        return 'Mixed';
    }
}
