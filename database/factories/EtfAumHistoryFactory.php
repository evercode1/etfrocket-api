<?php

namespace Database\Factories;

use App\Models\EtfAumHistory;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtfAumHistory>
 */
class EtfAumHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aumDate = $this->faker->unique()->date();

        return [

            'etf_id' => rand(1, 7),

            'aum_date' => $aumDate,

            'assets_under_management' => $this->faker->numberBetween(
                1000000,
                50000000000
            ),

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ];
    }
}
