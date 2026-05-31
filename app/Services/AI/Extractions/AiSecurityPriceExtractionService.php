<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSecurityPriceExtractionService
{
    public function extract(
        Security $security
    ): AiDataExtraction {

        $apiKey = config(
            'services.twelve_data.api_key'
        );

        if (! $apiKey) {

            throw new \RuntimeException(
                'Twelve Data API key missing.'
            );
        }

        $response =

            Http::timeout(30)

                ->get(

                    'https://api.twelvedata.com/quote',

                    [

                        'symbol' => $security->symbol,

                        'apikey' => $apiKey,

                    ]

                );

        if (! $response->successful()) {

            Log::error(

                'Twelve Data request failed.',

                [

                    'symbol' => $security->symbol,

                    'status' => $response->status(),

                    'body' => $response->body(),

                ]

            );

            throw new \RuntimeException(
                'Twelve Data request failed.'
            );
        }

        $data =
            $response->json();

        if (

            isset($data['status']) &&

            $data['status'] === 'error'

        ) {

            throw new \RuntimeException(
                $data['message']
                ??

                'Twelve Data error.'

            );
        }

        $closePrice =

            isset($data['close'])

            ? (float) $data['close']

            : null;

        $priceDate =

            $data['datetime']
            ?? null;

        $volume =

            isset($data['volume'])

            ? (int) $data['volume']

            : null;

        $extractedData = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'close_price' => $closePrice,

            'price_date' => $priceDate,

            'volume' => $volume,

        ];

        return AiDataExtraction::create([

            'security_id' => $security->id,

            'data_source_id' => DataSource::TWELVE_DATA_API, // Twelve Data

            'source_url' => 'https://api.twelvedata.com/quote?symbol='.

                $security->symbol,

            'raw_payload' => json_encode($data),

            'prompt' => 'Twelve Data quote extraction for '.

                $security->symbol,

            'extracted_data' => $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);

    }
}
