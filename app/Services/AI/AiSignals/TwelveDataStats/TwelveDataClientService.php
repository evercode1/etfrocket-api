<?php

namespace App\Services\AI\AiSignals\TwelveDataStats;

use Exception;
use Illuminate\Support\Facades\Http;

class TwelveDataClientService
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config(
            'services.twelve_data.base_url',
            'https://api.twelvedata.com'
        );

        $this->apiKey = config(
            'services.twelve_data.api_key'
        );
    }

    /**
     * Get quote information for a symbol.
     *
     * Example:
     *
     * [
     *     'symbol' => 'SPY',
     *     'price' => 648.52,
     *     'change_percent' => 0.83,
     * ]
     */
    public function getQuote(
        string $symbol
    ): array {

        $response = Http::get(

            "{$this->baseUrl}/quote",

            [

                'symbol' => $symbol,

                'apikey' => $this->apiKey,

            ]

        );

        if (
            ! $response->successful()
        ) {

            throw new Exception(
                "Failed to retrieve quote for {$symbol}: "

                .$response->status()

                .' '

                .$response->body()

            );
        }

        $data = $response->json();

        if (
            isset($data['code'])
        ) {

            throw new Exception(
                $data['message']
                    ?? "Twelve Data error for {$symbol}"
            );
        }

        return [

            'symbol' => $symbol,

            'price' => isset($data['close'])
                ? (float) $data['close']
                : null,

            'change_percent' => isset($data['percent_change'])
                ? (float) $data['percent_change']
                : null,

        ];
    }

    /**
     * Get daily historical prices.
     *
     * Returns:
     *
     * [
     *     [
     *         'datetime' => '2026-06-01',
     *         'close' => 648.52,
     *     ],
     * ]
     */
    public function getDailyHistory(
        string $symbol,
        int $days = 250
    ): array {

        $response = Http::get(

            "{$this->baseUrl}/time_series",

            [

                'symbol' => $symbol,

                'interval' => '1day',

                'outputsize' => $days,

                'apikey' => $this->apiKey,

            ]

        );

        if (
            ! $response->successful()
        ) {

            throw new Exception(
                "Failed to retrieve history for {$symbol}"
            );
        }

        $data = $response->json();

        if (
            isset($data['code'])
        ) {

            throw new Exception(
                $data['message']
                    ?? "Twelve Data error for {$symbol}"
            );
        }

        if (
            empty($data['values'])
        ) {

            return [];
        }

        return collect(

            $data['values']

        )

            ->map(

                function (
                    array $row
                ) {

                    return [

                        'datetime' => $row['datetime'],

                        'close' => (float) $row['close'],

                    ];
                }

            )

            ->values()

            ->toArray();
    }
}
