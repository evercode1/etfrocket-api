<?php

namespace App\Queries\Securities;

use App\Models\PerformanceRangeType;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use Illuminate\Support\Facades\Cache;

class SecuritySignalsQuery
{
    public function getData(int $securityId): array
    {
        return Cache::remember(

            "security_signals_{$securityId}",

            now()->addHours(6),

            function () use ($securityId) {

                return [

                    'distribution_growth' => $this->getDistributionSignal(
                        $securityId
                    ),

                    'aum_growth' => $this->getAumSignal(
                        $securityId
                    ),

                    'nav_stability' => $this->getNavSignal(
                        $securityId
                    ),

                ];
            }

        );
    }

    private function getDistributionSignal(
        int $securityId
    ): array {

        $recentYear = SecurityDividendHistory::query()
            ->where('security_id', $securityId)
            ->where(
                'payment_date',
                '>=',
                now()->subYear()->toDateString()
            )
            ->sum('dividend_amount');

        $previousYear = SecurityDividendHistory::query()
            ->where('security_id', $securityId)
            ->whereBetween(
                'payment_date',
                [
                    now()->subYears(2)->toDateString(),
                    now()->subYear()->toDateString(),
                ]
            )
            ->sum('dividend_amount');

        if ($previousYear <= 0) {

            return [
                'status' => 'unknown',
                'value' => null,
                'label' => 'Insufficient Distribution History',
            ];
        }

        $growthPercentage = round(

            (
                ($recentYear - $previousYear)
                /
                $previousYear
            ) * 100,

            4

        );

        return [

            'status' => match (true) {

                $growthPercentage > 5 => 'strong_growth',

                $growthPercentage > 0 => 'growth',

                $growthPercentage < -5 => 'decline',

                $growthPercentage < 0 => 'slight_decline',

                default => 'flat',

            },

            'value' => $growthPercentage,

            'label' => match (true) {

                $growthPercentage > 5 => 'Strong Distribution Growth',

                $growthPercentage > 0 => 'Distribution Growing',

                $growthPercentage < -5 => 'Distribution Declining',

                $growthPercentage < 0 => 'Slight Distribution Decline',

                default => 'Distribution Stable',

            },

            'recent_year_distributions' => round(
                $recentYear,
                4
            ),

            'previous_year_distributions' => round(
                $previousYear,
                4
            ),

        ];
    }

    private function getAumSignal(
        int $securityId
    ): array {

        $metric = SecurityMetric::query()
            ->where('security_id', $securityId)
            ->where(
                'performance_range_type_id',
                PerformanceRangeType::THIRTY_DAY
            )
            ->first();

        if (! $metric || is_null($metric->aum_change_percentage)) {

            return [
                'status' => 'unknown',
                'value' => null,
                'label' => 'No AUM Data',
            ];
        }

        $value = round(
            (float) $metric->aum_change_percentage,
            4
        );

        return [

            'status' => match (true) {

                $value > 5 => 'strong_inflow',

                $value > 0 => 'inflow',

                $value < -5 => 'strong_outflow',

                $value < 0 => 'outflow',

                default => 'neutral',

            },

            'value' => $value,

            'label' => match (true) {

                $value > 5 => 'Strong Investor Inflows',

                $value > 0 => 'Investor Inflows',

                $value < -5 => 'Strong Investor Outflows',

                $value < 0 => 'Investor Outflows',

                default => 'Flat Flows',

            },

        ];
    }

    private function getNavSignal(
        int $securityId
    ): array {

        $metric = SecurityMetric::query()
            ->where('security_id', $securityId)
            ->where(
                'performance_range_type_id',
                PerformanceRangeType::MAX
            )
            ->first();

        if (! $metric || is_null($metric->nav_erosion_percentage)) {

            return [
                'status' => 'unknown',
                'value' => null,
                'label' => 'No NAV Data',
            ];
        }

        $value = round(
            (float) $metric->nav_erosion_percentage,
            4
        );

        return [

            'status' => match (true) {

                $value >= -3 => 'stable',

                $value >= -10 => 'mixed',

                default => 'watch',

            },

            'value' => $value,

            'label' => match (true) {

                $value >= -3 => 'NAV Stable',

                $value >= -10 => 'Monitor NAV',

                default => 'NAV Erosion Risk',

            },

        ];
    }
}
