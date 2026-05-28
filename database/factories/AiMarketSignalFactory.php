<?php

namespace Database\Factories;

use App\Models\AiMarketSignal;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiMarketSignalFactory extends Factory
{
    protected $model =
        AiMarketSignal::class;

    public function definition()
    {
        return [

            'signal_type_id' => 1,

            'title' => $this->faker->sentence(3),

            'subtitle' => $this->faker->sentence(),

            'market_mood' => $this->faker->randomElement([

                'Risk-On',

                'Neutral',

                'Defensive',

                'Event Driven',

            ]),

            'confidence_score' => $this->faker->numberBetween(
                60,
                95
            ),

            'markdown_content' => <<<'MARKDOWN'
# Market Snapshot

Markets remained stable today as treasury yields softened and volatility declined.

## Key Signals

- Nasdaq momentum improving
- Bitcoin remains elevated
- Treasury yields stabilizing

## AI Interpretation

The AI system currently identifies improving breadth and moderate risk appetite across growth sectors.
MARKDOWN,

            'payload_json' => [

                'vix' => 14.2,

                'btc_trend' => 'bullish',

                'spy_trend' => 'neutral',

                'treasury_yield' => 4.21,

            ],

            'generated_at' => now(),

            'expires_at' => now()->addDay(),

            'is_active' => true,

            'ai_model' => 'gpt-5.5',

        ];
    }
}
