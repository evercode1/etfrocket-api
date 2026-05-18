<?php

namespace Database\Factories;

use App\Models\EtfNavHistory;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtfNavHistory>
 */
class EtfNavHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $navDate = $this->faker->unique()->date();

        return [

            'etf_id' => rand(1, 7),

            'nav_date' => $navDate,

            'nav_per_share' => $this->faker->randomFloat(4, 5, 500),

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ];
    }
}
