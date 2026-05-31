<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YieldMaxScraperService
{
    public function extract(
        Security $security
    ): array {

        $symbol = strtolower(
            $security->symbol
        );

        $url =
            "https://yieldmaxetfs.com/our-etfs/{$symbol}/";

        Log::info(

            'YIELDMAX SCRAPER STARTED',

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
                'Failed to retrieve YieldMax ETF page.'

            );
        }

        $html =
            $response->body();

        $aum = null;

        $nav = null;

        $sharesOutstanding = null;

        $asOfDate = null;

        preg_match(

            '/Net Assets:\<\/div\>\s*\<div class="fund-value"\>\$([^<]+)/i',

            $html,

            $aumMatch

        );

        if (

            ! empty($aumMatch[1])

        ) {

            $aum = $this->normalizeAum(
                trim(
                    $aumMatch[1]
                )
            );
        }

        preg_match(

            '/NAV:\<\/div\>\s*\<div class="fund-value"\>\$([^<]+)/i',

            $html,

            $navMatch

        );

        if (

            ! empty($navMatch[1])

        ) {

            $nav = (float)

                str_replace(

                    ',',

                    '',

                    trim(
                        $navMatch[1]
                    )

                );
        }

        preg_match(

            '/Shares Outstanding:\<\/div\>\s*\<div class="fund-value"\>([^<]+)/i',

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

        if (

            $aum === null

            &&

            $nav === null

        ) {

            throw new \RuntimeException(
                'YieldMax scraper could not locate fund data.'

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

            'YIELDMAX SCRAPER COMPLETE',

            $result

        );

        return $result;
    }

    private function normalizeAum(
        string $value
    ): ?int {

        $value = strtoupper(

            str_replace(

                [

                    '$',

                    ',',

                    ' ',

                ],

                '',

                $value

            )

        );

        if (

            ! preg_match(

                '/^([0-9\.]+)([MBT])?$/',

                $value,

                $matches

            )

        ) {

            return null;
        }

        $amount =
            (float) $matches[1];

        $suffix =
            $matches[2] ?? null;

        return match ($suffix) {

            'T' => (int)

                round(
                    $amount * 1000000000000
                ),

            'B' => (int)

                round(
                    $amount * 1000000000
                ),

            'M' => (int)

                round(
                    $amount * 1000000
                ),

            default => (int)

                round(
                    $amount
                ),

        };
    }
}
