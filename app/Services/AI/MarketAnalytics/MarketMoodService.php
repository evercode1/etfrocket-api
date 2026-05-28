<?php

namespace App\Services\AI\MarketAnalytics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketMoodService
{
    private array $allowedMoods = [

        'Euphoric',

        'Bullish',

        'Risk-On',

        'Neutral',

        'Risk-Off',

        'Bearish',

        'Panic',

        'Undetermined',

    ];

    public function determine(): array
    {

        try {

            $response =

                Http::withHeaders([

                    'Authorization' => 'Bearer '.

                        config(

                            'services.openai.api_key'

                        ),

                    'Content-Type' => 'application/json',

                ])
                    ->timeout(30)
                    ->post(

                        'https://api.openai.com/v1/responses',

                        [

                            'model' => config(

                                'services.openai.model',

                                'gpt-4.1-mini'

                            ),

                            'input' => [

                                [

                                    'role' => 'system',

                                    'content' => 'You are a financial market sentiment classifier.

Return ONLY ONE of the following exact values:

Euphoric

Bullish

Risk-On

Neutral

Risk-Off

Bearish

Panic

Undetermined

Do not explain your reasoning.

Do not include punctuation.

Do not include markdown.

Do not include additional text.',

                                ],

                                [

                                    'role' => 'user',

                                    'content' => 'Analyze the current Nasdaq market environment, momentum, volatility, and macro sentiment and classify the current market mood.',

                                ],

                            ],

                        ]

                    );

            if (! $response->successful()) {

                return $this->fallback();
            }

            $marketMood =

                trim(

                    data_get(

                        $response->json(),

                        'output.0.content.0.text',

                        'Undetermined'

                    )

                );

            /*

            |--------------------------------------------------------------------------

            | Strict Validation

            |--------------------------------------------------------------------------

            */

            if (

                ! in_array(

                    $marketMood,

                    $this->allowedMoods

                )

            ) {

                return $this->fallback();
            }

            return [

                'market_mood' => $marketMood,

                'confidence_score' => $this->getConfidenceScore(

                    $marketMood

                ),

            ];
        } catch (\Throwable $e) {

            Log::error(

                'Failed determining market mood.',

                [

                    'message' => $e->getMessage(),

                ]

            );

            return $this->fallback();
        }
    }

    private function getConfidenceScore(

        string $marketMood

    ): int {

        return match ($marketMood) {

            'Euphoric' => 95,

            'Bullish' => 88,

            'Risk-On' => 82,

            'Neutral' => 70,

            'Risk-Off' => 79,

            'Bearish' => 90,

            'Panic' => 97,

            default => 50,
        };
    }

    private function fallback(): array
    {

        return [

            'market_mood' => 'Undetermined',

            'confidence_score' => 50,

        ];
    }
}
