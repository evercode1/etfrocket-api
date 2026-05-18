<?php

namespace Database\Factories;

use App\Models\PortfolioTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

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

            'etf_id' => $etfId = rand(1, 4),

            'transaction_type_id' => 1,

            /*
    |--------------------------------------------------------------------------
    | Realistic Share Counts
    |--------------------------------------------------------------------------
    */

            'shares' => match ($etfId) {

                1 => $this->faker->randomFloat(4, 5, 250),   // AMDY
                2 => $this->faker->randomFloat(4, 5, 200),   // NVII
                3 => $this->faker->randomFloat(4, 5, 300),   // CHPY
                4 => $this->faker->randomFloat(4, 5, 200),   // GOOY

                default => $this->faker->randomFloat(4, 1, 100),
            },

            /*
    |--------------------------------------------------------------------------
    | Realistic Historical Price Ranges
    |--------------------------------------------------------------------------
    */

            'price_per_share' => match ($etfId) {

                1 => $this->faker->randomFloat(4, 18, 35), // AMDY
                2 => $this->faker->randomFloat(4, 22, 35), // NVII
                3 => $this->faker->randomFloat(4, 20, 40), // CHPY
                4 => $this->faker->randomFloat(4, 18, 35), // GOOY

                default => $this->faker->randomFloat(4, 10, 100),
            },

            /*
    |--------------------------------------------------------------------------
    | Realistic Historical Date Spread
    |--------------------------------------------------------------------------
    */

            'transaction_date' => $this->faker->dateTimeBetween(
                '2025-05-28',
                'now'
            )->format('Y-m-d'),

        ];
    }
}
