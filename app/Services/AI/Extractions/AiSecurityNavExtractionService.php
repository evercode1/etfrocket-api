<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSecurityNavExtractionService
{
    public function extract(
        Security $security
    ): AiDataExtraction {

        $apiKey = config(
            'services.twelve_data.api_key'
        );

        Log::info(
            'EXTRACT NAV METHOD HIT',
            [
                'symbol' => $security->symbol,
            ]
        );

        $url =
            'https://api.twelvedata.com/quote?'.

            'symbol='.
                $security->symbol.

            '&apikey='.
                $apiKey;

        Log::info('NAV URL', [

            'url' => $url,

        ]);

        $response =

            Http::timeout(60)
                ->get($url);

        if (! $response->successful()) {

            Log::error(
                'Twelve Data NAV extraction failed.',
                [
                    'security_id' => $security->id,
                    'symbol' => $security->symbol,
                    'response' => $response->body(),
                ]
            );

            throw new \RuntimeException(
                'NAV extraction failed.'
            );
        }

        $data =
            $response->json();

        Log::info(
            'TWELVE DATA NAV RESPONSE',
            [
                'symbol' => $security->symbol,
                'response' => $data,
            ]
        );

        if (

            empty($data['close'])

        ) {

            Log::info(

                'NO NAV DATA AVAILABLE',

                [

                    'symbol' => $security->symbol,

                ]

            );

            return AiDataExtraction::create([

                'security_id' => $security->id,

                'data_source_id' => DataSource::TWELVE_DATA_API,

                'source_url' => $url,

                'raw_payload' => json_encode(
                    $data
                ),

                'prompt' => 'Twelve Data NAV extraction for '.
                    $security->symbol,

                'extracted_data' => [

                    'symbol' => strtoupper(
                        $security->symbol
                    ),

                    'nav_per_share' => null,

                    'nav_date' => null,

                ],

                'is_validated' => false,

                'validation_notes' => 'No NAV data available from Twelve Data.',

                'processed_at' => now(),

            ]);
        }

        $extractedData = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'nav_per_share' => (float)
                $data['close'],

            'nav_date' => $data['datetime']
                ?? now()->toDateString(),

        ];

        Log::info(
            'TWELVE DATA NAV EXTRACTION',
            $extractedData
        );

        return AiDataExtraction::create([

            'security_id' => $security->id,

            'data_source_id' => DataSource::TWELVE_DATA_API,

            'source_url' => $url,

            'raw_payload' => json_encode(
                $data
            ),

            'prompt' => 'Twelve Data NAV extraction for '.
                $security->symbol,

            'extracted_data' => $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);
    }
}
