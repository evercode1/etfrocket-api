<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSecurityDividendExtractionService
{
    public function extract(
        Security $security
    ): AiDataExtraction {

        $apiKey = config(
            'services.twelve_data.api_key'
        );

        Log::info(
            'EXTRACT DIVIDEND METHOD HIT',
            [
                'symbol' => $security->symbol,
            ]
        );

        $url =
            'https://api.twelvedata.com/dividends?'.

            'symbol='.
                $security->symbol.

            '&apikey='.
                $apiKey;

        Log::info('DIVIDEND URL', [

            'url' => $url,

        ]);

        $response =

            Http::timeout(60)
                ->get($url);

        if (! $response->successful()) {

            Log::error(
                'Twelve Data dividend extraction failed.',
                [
                    'security_id' => $security->id,
                    'symbol' => $security->symbol,
                    'response' => $response->body(),
                ]
            );

            throw new \RuntimeException(
                'Dividend extraction failed.'
            );
        }

        $data =
            $response->json();

        Log::info(
            'TWELVE DATA DIVIDEND RESPONSE',
            [
                'symbol' => $security->symbol,
                'response' => $data,
            ]
        );

        $latestDividend =
            $data['dividends'][0]
                ?? $data['data'][0]
                ?? null;

        if (! $latestDividend) {

            throw new \RuntimeException(
                'No dividend data returned.'
            );
        }

        $extractedData = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'dividend_amount' => isset(
                $latestDividend['amount']
            )
                    ? (float)
                        $latestDividend['amount']
                    : null,

            'ex_dividend_date' => $latestDividend['ex_date']
                    ?? null,

            'payment_date' => $latestDividend['payment_date']
                    ?? null,

        ];

        Log::info(
            'TWELVE DATA DIVIDEND EXTRACTION',
            $extractedData
        );

        return AiDataExtraction::create([

            'security_id' => $security->id,

            'data_source_id' => DataSource::TWELVE_DATA_API, // Twelve Data

            'source_url' => $url,

            'raw_payload' => json_encode(
                $data
            ),

            'prompt' => 'Twelve Data dividend extraction for '.
                $security->symbol,

            'extracted_data' => $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);
    }
}
