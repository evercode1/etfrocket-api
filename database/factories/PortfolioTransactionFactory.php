<?php

namespace Database\Factories;

use App\Models\PortfolioTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

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
        $etfId = rand(1, 4);

        return [

            'portfolio_id' => rand(1, 2),

            /*
            |--------------------------------------------------------------------------
            | ETFs Currently Seeded With Realistic History
            |--------------------------------------------------------------------------
            |
            | 1 = AMDY
            | 2 = NVII
            | 3 = CHPY
            | 4 = GOOY
            |
            */

            'etf_id' => $etfId,

            'transaction_type_id' => 1,

            /*
            |--------------------------------------------------------------------------
            | Realistic Share Counts
            |--------------------------------------------------------------------------
            */

            'shares' => match ($etfId) {

                1 => $this->randomFloat(5, 250), // AMDY

                2 => $this->randomFloat(5, 200), // NVII

                3 => $this->randomFloat(5, 300), // CHPY

                4 => $this->randomFloat(5, 200), // GOOY

                default => $this->randomFloat(1, 100),
            },

            /*
            |--------------------------------------------------------------------------
            | Realistic Historical Price Ranges
            |--------------------------------------------------------------------------
            */

            'price_per_share' => match ($etfId) {

                1 => $this->randomFloat(18, 35), // AMDY

                2 => $this->randomFloat(22, 35), // NVII

                3 => $this->randomFloat(20, 40), // CHPY

                4 => $this->randomFloat(18, 35), // GOOY

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
