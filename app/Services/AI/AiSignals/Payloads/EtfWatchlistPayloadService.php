<?php

namespace App\Services\AI\AiSignals\Payloads;

use App\Services\AI\AiSignals\Watchlists\EtfWatchlistService;

class EtfWatchlistPayloadService
{
    public function __construct(
        private EtfWatchlistService $etfWatchlistService
    ) {}

    /**
     * Build a structured payload for the
     * ETF Watchlist AI signal.
     *
     * The goal is to provide GPT with
     * the most important ETF rankings
     * while also surfacing notable
     * leaders across each category.
     */
    public function getData(): array
    {
        $watchlists =
            $this->etfWatchlistService
                ->getData();

        return [

            'generated_at' => now()->toDateTimeString(),

            'top_performers' => $watchlists['top_performers'],

            'price_movers' => $watchlists['price_movers'],

            'aum_growth' => $watchlists['aum_growth'],

            'nav_health' => $watchlists['nav_health'],

            'watchlist_summary' => [

                'strongest_performer' => $watchlists['top_performers'][0]
                        ?? null,

                'strongest_price_mover' => $watchlists['price_movers'][0]
                        ?? null,

                'strongest_aum_growth' => $watchlists['aum_growth'][0]
                        ?? null,

                'strongest_nav_health' => $watchlists['nav_health'][0]
                        ?? null,

            ],

        ];
    }
}
