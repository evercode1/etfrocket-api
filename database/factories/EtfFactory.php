<?php

namespace Database\Factories;

use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etf>
 */
class EtfFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'symbol' => strtoupper($this->faker->unique()->lexify('????')),

            'fund_name' => $this->faker->company().' ETF',

            'etf_issuer_id' => EtfIssuer::first()?->id ?? 1,

            'etf_strategy_type_id' => EtfStrategyType::first()?->id ?? 1,

            'distribution_frequency_id' => DistributionFrequency::first()?->id ?? 1,

            'status_id' => Status::ACTIVE,

            'expense_ratio' => $this->faker->randomFloat(2, 0.10, 1.50),

            'inception_date' => $this->faker->date(),

            'source' => 'manual',

            'website_url' => $this->faker->url(),

            'notes' => $this->faker->sentence(),

        ];
    }
}
