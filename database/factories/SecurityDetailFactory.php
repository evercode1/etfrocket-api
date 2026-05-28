<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityDetailFactory extends Factory
{
    public function definition(): array
    {
        return [

            'security_id' => 1,

            /*
            |--------------------------------------------------------------------------
            | General Security Metadata
            |--------------------------------------------------------------------------
            */

            'security_name' => $this->faker->company(),

            'exchange' => $this->faker->randomElement([

                'NYSE',

                'NASDAQ',

                'CBOE',

            ]),

            'sector' => $this->faker->randomElement([

                'Technology',

                'Financial',

                'Healthcare',

                'Energy',

                'Consumer Staples',

            ]),

            'industry' => $this->faker->randomElement([

                'Software',

                'Semiconductors',

                'Banks',

                'Biotech',

                'Oil & Gas',

            ]),

            'inception_date' => $this->faker->date(),

            'source' => 'official_issuer_page',

            'website_url' => $this->faker->url(),

            'notes' => $this->faker->optional()
                ->sentence(),

            /*
            |--------------------------------------------------------------------------
            | ETF Metadata
            |--------------------------------------------------------------------------
            */

            'etf_issuer_id' => null,

            'etf_strategy_type_id' => null,

            'distribution_frequency_id' => null,

            'expense_ratio' => null,

        ];
    }
}
