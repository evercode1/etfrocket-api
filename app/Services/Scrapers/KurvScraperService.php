<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KurvScraperService
{
    private const BASE_URL =
        'https://web.services.kurvinvest.com/etfdata';

    public function extract(
        Security $security
    ): array {

        Log::info(

            'KURV SCRAPER STARTED',

            [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

            ]

        );

        $response =

            Http::timeout(60)
                ->get(

                    self::BASE_URL.'/'.

                    strtoupper(
                        $security->symbol
                    ).

                    '/latest_price.json'

                );

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Failed to retrieve Kurv ETF data.'

            );
        }

        $data =
            $response->json();

        if (

            empty($data)

            ||

            ! isset(
                $data['NAVCents']
            )

            ||

            ! isset(
                $data['lastTradingDate']
            )

            ||

            ! isset(
                $data['Holdings']
            )

        ) {

            throw new \RuntimeException(
                'Kurv fund data is incomplete.'

            );
        }

        $assetsUnderManagement =

            collect(
                $data['Holdings']
            )

                ->sum(
                    'MarketValueCents'
                )

                / 100;

        $navPerShare =

            round(

                $data['NAVCents']
                / 100,

                4

            );

        if (

            $navPerShare <= 0

        ) {

            throw new \RuntimeException(
                'Invalid NAV returned from Kurv.'

            );
        }

        $sharesOutstanding =

            (int) round(

                $assetsUnderManagement

                /

                $navPerShare

            );

        $date =

            Carbon::parse(

                $data['lastTradingDate']

            )->toDateString();

        $result = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'assets_under_management' => round(
                $assetsUnderManagement,
                2
            ),

            'aum_date' => $date,

            'nav_per_share' => $navPerShare,

            'nav_date' => $date,

            'shares_outstanding' => $sharesOutstanding,

        ];

        Log::info(

            'KURV SCRAPER COMPLETE',

            $result

        );

        return $result;
    }
}
