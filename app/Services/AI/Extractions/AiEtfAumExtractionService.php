<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEtfAumExtractionService
{
    public function extract(
        Etf $etf
    ): AiDataExtraction {

        $prompt =
            $this->buildPrompt(
                $etf
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

                    'model' =>

                    config(

                        'services.openai.model',

                        'gpt-4.1-mini'

                    ),

                    'input' => [

                        [

                            'role' => 'system',

                            'content' =>

                            'You extract ETF AUM data and return only valid JSON matching the required schema.',

                        ],

                        [

                            'role' => 'user',

                            'content' =>

                            $prompt,

                        ],

                    ],

                    'text' => [

                        'format' => [

                            'type' =>
                            'json_schema',

                            'name' =>
                            'etf_aum_extraction',

                            'schema' =>
                            $this->schema(),

                            'strict' => true,

                        ],

                    ],

                ]

            );

        if (! $response->successful()) {

            Log::error(

                'OpenAI ETF AUM extraction failed.',

                [

                    'etf_id' =>
                    $etf->id,

                    'symbol' =>
                    $etf->symbol,

                    'response' =>
                    $response->json(),

                ]

            );

            throw new \RuntimeException(
                'AI ETF AUM extraction failed.'
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
                'AI ETF AUM extraction returned invalid JSON.'
            );
        }

        return AiDataExtraction::create([

            'etf_id' =>
            $etf->id,

            'data_source_id' =>
            $etf->data_source_id
                ?? null,

            'source_url' =>
            $etf->website_url,

            'raw_payload' =>
            $response->body(),

            'prompt' =>
            $prompt,

            'extracted_data' =>
            $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);
    }

    private function buildPrompt(
        Etf $etf
    ): string {

        $currentDate =

            Carbon::now()->format(
                'Y-m-d'
            );

        return "

You are extracting ETF assets under management data for Etf Rocket.

Today's date: {$currentDate}

ETF Symbol:
{$etf->symbol}

ETF Name:
{$etf->fund_name}

Official Website:
{$etf->website_url}

Extract ONLY:

- assets_under_management
- aum_date

Rules:

- Use the MOST RECENT published AUM.
- Convert abbreviations into full numeric values.
- Example: 1.2B = 1200000000
- Do not guess.
- Dates must be YYYY-MM-DD.
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

                'assets_under_management',

                'aum_date',

            ],

            'properties' => [

                'symbol' => [

                    'type' => 'string',

                ],

                'assets_under_management' => [

                    'type' => [

                        'integer',

                        'null',

                    ],

                ],

                'aum_date' => [

                    'type' => [

                        'string',

                        'null',

                    ],

                ],

            ],

        ];
    }
}
