<?php

namespace Database\Factories;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiDataExtraction>
 */
class AiDataExtractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'etf_id' => 6,

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'source_url' => $this->faker->url(),

            'raw_payload' => json_encode([
                'symbol' => 'LFGY',
                'price' => '12.52',
                'nav' => '12.34',
                'aum' => '123456789',
                'dividend' => '0.2635',
            ]),

            'prompt' => 'Extract ETF price, NAV, AUM, and dividend data from the provided source payload.',

            'extracted_data' => [

                'symbol' => 'LFGY',

                'price' => [

                    'close_price' => 12.52,

                    'price_date' => now()->toDateString(),

                    'volume' => 1234567,

                ],

                'nav' => [

                    'nav_per_share' => 12.34,

                    'as_of_date' => now()->toDateString(),

                ],

                'aum' => [

                    'assets_under_management' => 123456789,

                    'as_of_date' => now()->toDateString(),

                ],

                'dividend' => [

                    'dividend_amount' => 0.2635,

                    'ex_dividend_date' => now()->toDateString(),

                    'payment_date' => now()->addDays(2)->toDateString(),

                ],

            ],

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ];
    }
}
