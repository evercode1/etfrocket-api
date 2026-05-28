<?php

namespace Database\Factories;

use App\Models\DataSource;
use App\Models\SecurityNavHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityNavHistory>
 */
class SecurityNavHistoryFactory extends Factory
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

            'security_id' => rand(1, 4),

            'nav_date' => $navDate,

            'nav_per_share' => $this->faker->randomFloat(4, 5, 500),

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ];
    }
}
