<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSecurityDividendExtractionService
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

                                'content' => 'You extract ETF dividend data and return only valid JSON matching the required schema.',

                            ],

                            [

                                'role' => 'user',

                                'content' => $prompt,

                            ],

                        ],

                        'text' => [

                            'format' => [

                                'type' => 'json_schema',

                                'name' => 'etf_dividend_extraction',

                                'schema' => $this->schema(),

                                'strict' => true,

                            ],

                        ],

                    ]

                );

        if (! $response->successful()) {

            Log::error(

                'OpenAI ETF dividend extraction failed.',

                [

                    'security_id' => $security->id,

                    'symbol' => $security->symbol,

                    'response' => $response->json(),

                ]

            );

            throw new \RuntimeException(
                'AI Security dividend extraction failed.'
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
                'AI ETF dividend extraction returned invalid JSON.'
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

You are extracting Security dividend data for Etf Rocket.

Today's date: {$currentDate}

Security Symbol:
{$security->symbol}

Security Name:
{$security->fund_name}

Official Website:
{$security->website_url}

Extract ONLY:

- dividend_amount
- ex_dividend_date
- payment_date

Rules:

- Use the MOST RECENT announced or paid dividend.
- Do not use dividend yield.
- Do not guess.
- Dates must be YYYY-MM-DD.
- Numbers must be raw numeric values.
- Return ONLY valid JSON matching the schema.
- Use the MOST RECENT confirmed dividend with a published dividend_amount.
- Only return dividends where dividend_amount is officially available.
- Do not return placeholder announcements or future ex-dividend dates without confirmed payout amounts.
- If no confirmed dividend_amount exists, do not include the dividend in results.

";
    }

    private function schema(): array
    {
        return [

            'type' => 'object',

            'additionalProperties' => false,

            'required' => [

                'symbol',

                'dividend_amount',

                'ex_dividend_date',

                'payment_date',

            ],

            'properties' => [

                'symbol' => [

                    'type' => 'string',

                ],

                'dividend_amount' => [

                    'type' => [

                        'number',

                        'null',

                    ],

                ],

                'ex_dividend_date' => [

                    'type' => [

                        'string',

                        'null',

                    ],

                ],

                'payment_date' => [

                    'type' => [

                        'string',

                        'null',

                    ],

                ],

            ],

        ];
    }
}
