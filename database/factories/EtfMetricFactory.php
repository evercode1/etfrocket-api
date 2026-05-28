<?php

namespace Database\Factories;

use App\Models\EtfMetric;
use App\Models\MetricDirection;
use App\Models\PerformanceRangeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtfMetric>
 */
class EtfMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $startDate = now()->subDays(30)->toDateString();
        $endDate = now()->toDateString();

        $startPrice = $this->faker->randomFloat(4, 10, 100);
        $endPrice = $this->faker->randomFloat(4, 10, 100);

        $priceChange = $endPrice - $startPrice;
        $priceChangePercentage = ($priceChange / $startPrice) * 100;

        $startNav = $this->faker->randomFloat(4, 10, 100);
        $endNav = $this->faker->randomFloat(4, 10, 100);

        $navChange = $endNav - $startNav;
        $navErosionPercentage = ($navChange / $startNav) * 100;

        $startAum = $this->faker->numberBetween(1000000, 5000000000);
        $endAum = $this->faker->numberBetween(1000000, 5000000000);

        $aumChange = $endAum - $startAum;
        $aumChangePercentage = ($aumChange / $startAum) * 100;

        return [

            'etf_id' => rand(1, 7),

            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

            'start_date' => $startDate,

            'end_date' => $endDate,

            'start_price' => $startPrice,

            'end_price' => $endPrice,

            'price_change' => round($priceChange, 4),

            'price_change_percentage' => round($priceChangePercentage, 4),

            'dividends_paid' => $this->faker->randomFloat(4, 0, 10),

            'dividend_count' => $this->faker->numberBetween(0, 10),

            'average_dividend' => $this->faker->randomFloat(4, 0, 2),

            'total_return_percentage' => $this->faker->randomFloat(4, -25, 50),

            'start_nav' => $startNav,

            'end_nav' => $endNav,

            'nav_change' => round($navChange, 4),

            'nav_erosion_percentage' => round($navErosionPercentage, 4),

            'nav_direction_id' => MetricDirection::FLAT,

            'start_aum' => $startAum,

            'end_aum' => $endAum,

            'aum_change' => $aumChange,

            'aum_change_percentage' => round($aumChangePercentage, 4),

            'aum_direction_id' => MetricDirection::FLAT,

            'calculated_at' => now(),

        ];
    }
}
