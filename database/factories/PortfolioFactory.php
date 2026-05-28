<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portfolio>
 */
class PortfolioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'user_id' => rand(1, 100),

            'status_id' => Status::ACTIVE,

            'portfolio_name' => $this->faker->words(2, true).' Portfolio',

            'is_default' => false,

        ];
    }
}
