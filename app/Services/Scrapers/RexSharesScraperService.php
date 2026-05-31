<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RexSharesScraperService
{
    public function extract(
        Security $security
    ): array {

        $symbol = strtolower(
            $security->symbol
        );

        $url =
            "https://www.rexshares.com/{$symbol}/";

        Log::info(

            'REX SHARES SCRAPER STARTED',

            [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

                'url' => $url,

            ]

        );

        $response =

            Http::timeout(60)
                ->get($url);

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Failed to retrieve REX Shares ETF page.'
            );
        }

        $html =
            $response->body();

        $nav = null;

        $aum = null;

        $sharesOutstanding = null;

        $asOfDate = null;

        preg_match(

            '/As of\s+([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i',

            $html,

            $dateMatch

        );

        if (

            ! empty($dateMatch[1])

        ) {

            $asOfDate =

                Carbon::createFromFormat(

                    'm/d/Y',

                    trim(
                        $dateMatch[1]
                    )

                )
                    ->toDateString();
        }

        preg_match(
            '/<div class="t-col t-label">\s*NAV\s*<\/div>\s*<div class="t-col t-data">\s*\$?([^<]+)\s*<\/div>/is',
            $html,
            $navMatch
        );

        if (

            ! empty($navMatch[1])

        ) {

            $nav =

                round(

                    (float)

                    str_replace(

                        ',',

                        '',

                        trim(
                            $navMatch[1]
                        )

                    ),

                    4

                );
        }

        preg_match(
            '/<div class="t-col t-label">\s*Fund Assets\s*<\/div>\s*<div class="t-col t-data">\s*\$?([^<]+)\s*<\/div>/is',
            $html,
            $aumMatch
        );

        if (

            ! empty($aumMatch[1])

        ) {

            $aum =

                (int)

                round(

                    (float)

                    str_replace(

                        ',',

                        '',

                        trim(
                            $aumMatch[1]
                        )

                    )

                );
        }

        preg_match(
            '/<div class="t-col t-label">\s*Shares Outstanding\s*<\/div>\s*<div class="t-col t-data">\s*([^<]+)\s*<\/div>/is',
            $html,
            $sharesMatch
        );

        if (

            ! empty($sharesMatch[1])

        ) {

            $sharesOutstanding =

                (int)

                str_replace(

                    ',',

                    '',

                    trim(
                        $sharesMatch[1]
                    )

                );
        }

        if (

            $aum === null

            &&

            $nav === null

        ) {

            throw new \RuntimeException(
                'REX Shares scraper could not locate fund data.'

            );
        }

        $result = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'assets_under_management' => $aum,

            'aum_date' => $asOfDate,

            'nav_per_share' => $nav,

            'nav_date' => $asOfDate,

            'shares_outstanding' => $sharesOutstanding,

        ];

        Log::info(

            'REX SHARES SCRAPER COMPLETE',

            $result

        );

        return $result;
    }
}
