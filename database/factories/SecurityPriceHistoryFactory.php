<?php

namespace Database\Factories;

use App\Models\DataSource;
use App\Models\SecurityPriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityPriceHistory>
 */
class SecurityPriceHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'security_id' => rand(1, 7),

            'price_date' => $this->faker->unique()->date(),

            'close_price' => $this->faker->randomFloat(4, 5, 500),

            'volume' => $this->faker->numberBetween(10000, 100000000),

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ];
    }
}
