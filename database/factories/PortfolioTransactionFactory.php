<?php

namespace Database\Factories;

use App\Models\PortfolioTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<PortfolioTransaction>
 */
class PortfolioTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $securityId = Arr::random([8, 25, 106, 119]);

        return [

            'portfolio_id' => rand(1, 2),

            'security_id' => $securityId,

            'transaction_type_id' => 1,

            /*
            |--------------------------------------------------------------------------
            | Realistic Share Counts
            |--------------------------------------------------------------------------
            */

            'shares' => match ($securityId) {

                1 => $this->randomFloat(5, 250), // AAPW

                2 => $this->randomFloat(5, 200), // AAPY

                3 => $this->randomFloat(5, 300), // ABNY

                4 => $this->randomFloat(5, 200), // AIPI

                default => $this->randomFloat(1, 100),
            },

            /*
            |--------------------------------------------------------------------------
            | Realistic Historical Price Ranges
            |--------------------------------------------------------------------------
            */

            'price_per_share' => match ($securityId) {

                1 => $this->randomFloat(18, 35), // AAPW

                2 => $this->randomFloat(22, 35), // AAPY

                3 => $this->randomFloat(20, 40), // ABNY

                4 => $this->randomFloat(18, 35), // AIPI

                default => $this->randomFloat(10, 100),
            },

            /*
            |--------------------------------------------------------------------------
            | Realistic Historical Date Spread
            |--------------------------------------------------------------------------
            */

            'transaction_date' => Carbon::create(
                2025,
                5,
                28
            )

                ->addDays(
                    rand(0, 365)
                )

                ->format('Y-m-d'),

        ];
    }

    private function randomFloat(
        float $min,
        float $max,
        int $decimals = 4
    ): float {

        $multiplier =
            pow(10, $decimals);

        return round(

            mt_rand(

                (int) ($min * $multiplier),

                (int) ($max * $multiplier)

            ) / $multiplier,

            $decimals

        );
    }
}
