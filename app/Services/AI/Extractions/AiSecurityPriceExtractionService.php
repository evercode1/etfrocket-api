<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSecurityPriceExtractionService
{
    public function extract(
        Security $security
    ): AiDataExtraction {

        $prompt =
            $this->buildPrompt(
                $security
            );

        $response =

            Http::withToken(
                config(
                    'services.openai.api_key'
                )
            )
                ->timeout(60)
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

                                'content' => 'You extract ETF price data and return only valid JSON matching the required schema.',

                            ],

                            [

                                'role' => 'user',

                                'content' => $prompt,

                            ],

                        ],

                        'text' => [

                            'format' => [

                                'type' => 'json_schema',

                                'name' => 'etf_price_extraction',

                                'schema' => $this->schema(),

                                'strict' => true,

                            ],

                        ],

                    ]

                );

        if (! $response->successful()) {

            Log::error(

                'OpenAI security price extraction failed.',

                [

                    'security_id' => $security->id,

                    'symbol' => $security->symbol,

                    'response' => $response->json(),

                ]

            );

            throw new \RuntimeException(
                'AI security price extraction failed.'
            );
        }

        $content =

            $response->json(
                'output.0.content.0.text'
            );

        $extractedData =

            json_decode(
                $content,
                true
            );

        if (! is_array($extractedData)) {

            throw new \RuntimeException(
                'AI security price extraction returned invalid JSON.'
            );
        }

        return AiDataExtraction::create([

            'security_id' => $security->id,

            'data_source_id' => $security->data_source_id
                ?? null,

            'source_url' => $security->website_url,

            'raw_payload' => $response->body(),

            'prompt' => $prompt,

            'extracted_data' => $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);
    }

    private function buildPrompt(
        Security $security
    ): string {

        $currentDate =

            Carbon::now()->format(
                'Y-m-d'
            );

        return "

You are extracting ETF closing price data for Etf Rocket.

Today's date: {$currentDate}

Security Symbol:
{$security->symbol}

Security Name:
{$security->fund_name}

Official Website:
{$security->website_url}

Find the MOST RECENT completed trading session closing data for this security.

Extract ONLY:

- close_price
- price_date
- volume

Rules:

- Use ONLY the latest completed trading session.
- Never use intraday pricing.
- Never use premarket pricing.
- Never use after-hours pricing.
- If markets are currently open, use the PREVIOUS completed session.
- If data cannot be verified, return null.
- Do not guess.
- Dates must be YYYY-MM-DD.
- Numbers must be raw numeric values.
- Return ONLY valid JSON matching the schema.

";
    }

    private function schema(): array
    {
        return [

            'type' => 'object',

            'additionalProperties' => false,

            'required' => [

                'symbol',

                'close_price',

                'price_date',

                'volume',

            ],

            'properties' => [

                'symbol' => [

                    'type' => 'string',

                ],

                'close_price' => [

                    'type' => [

                        'number',

                        'null',

                    ],

                ],

                'price_date' => [

                    'type' => [

                        'string',

                        'null',

                    ],

                ],

                'volume' => [

                    'type' => [

                        'integer',

                        'null',

                    ],

                ],

            ],

        ];
    }
}
