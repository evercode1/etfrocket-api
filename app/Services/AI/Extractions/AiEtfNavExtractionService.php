<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEtfNavExtractionService
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

                                'content' => 'You extract ETF NAV data and return only valid JSON matching the required schema.',

                            ],

                            [

                                'role' => 'user',

                                'content' => $prompt,

                            ],

                        ],

                        'text' => [

                            'format' => [

                                'type' => 'json_schema',

                                'name' => 'etf_nav_extraction',

                                'schema' => $this->schema(),

                                'strict' => true,

                            ],

                        ],

                    ]

                );

        if (! $response->successful()) {

            Log::error(

                'OpenAI ETF NAV extraction failed.',

                [

                    'security_id' => $security->id,

                    'symbol' => $security->symbol,

                    'response' => $response->json(),

                ]

            );

            throw new \RuntimeException(
                'AI ETF NAV extraction failed.'
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
                'AI ETF NAV extraction returned invalid JSON.'
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

You are extracting ETF NAV data for Etf Rocket.

Today's date: {$currentDate}

Security Symbol:
{$security->symbol}

Security Name:
{$security->security_name}

Official Website:
{$security->website_url}

Extract ONLY:

- nav_per_share
- nav_date

Rules:

- Use the MOST RECENT officially published NAV.
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

                'nav_per_share',

                'nav_date',

            ],

            'properties' => [

                'symbol' => [

                    'type' => 'string',

                ],

                'nav_per_share' => [

                    'type' => [

                        'number',

                        'null',

                    ],

                ],

                'nav_date' => [

                    'type' => [

                        'string',

                        'null',

                    ],

                ],

            ],

        ];
    }
}
