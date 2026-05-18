<?php

namespace Database\Factories;

use App\Models\EtfDividendHistory;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtfDividendHistory>
 */
class EtfDividendHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exDividendDate = $this->faker->unique()->date();

        return [

            'etf_id' => rand(1, 7),

            'dividend_amount' => $this->faker->randomFloat(4, 0.01, 5.00),

            'ex_dividend_date' => $exDividendDate,

            'payment_date' => $this->faker->dateTimeBetween($exDividendDate, '+14 days')->format('Y-m-d'),

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ];
    }
}
