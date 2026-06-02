<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class NeosScraperService
{
    public function extract(
        Security $security
    ): array {

        $symbol = strtoupper(
            $security->symbol
        );

        $url = sprintf(

            'https://neosfunds.com/%s/',

            strtolower($symbol)

        );

        $response = Http::timeout(30)

            ->withHeaders([

                'User-Agent' => 'Mozilla/5.0',

                'Accept' => 'text/html,application/xhtml+xml',

            ])

            ->get($url);

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Unable to retrieve NEOS ETF page.'

            );
        }

        $html = $response->body();

        preg_match(

            '/Net Assets<\/td>.*?\$([0-9,]+)/is',

            $html,

            $aumMatch

        );

        preg_match(

            '/Shares Outstanding<\/td>.*?([0-9,]+)\s*<\/td>/is',

            $html,

            $sharesMatch

        );

        preg_match(

            '/Net Asset Value<\/td>.*?\$([0-9\.]+)/is',

            $html,

            $navMatch

        );

        preg_match(

            '/As of:\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/is',

            $html,

            $dateMatch

        );

        $aum = isset(
            $aumMatch[1]
        )

            ? (float) str_replace(
                ',',
                '',
                $aumMatch[1]
            )

            : null;

        $sharesOutstanding = isset(
            $sharesMatch[1]
        )

            ? (int) str_replace(
                ',',
                '',
                $sharesMatch[1]
            )

            : null;

        $nav = isset(
            $navMatch[1]
        )

            ? (float) $navMatch[1]

            : null;

        $date = isset(
            $dateMatch[1]
        )

            ? Carbon::createFromFormat(

                'm/d/Y',

                $dateMatch[1]

            )->toDateString()

            : null;

        if (

            $aum === null ||

            $nav === null

        ) {

            throw new \RuntimeException(
                'NEOS scraper could not locate fund data.'

            );
        }

        return [

            'symbol' => $symbol,

            'assets_under_management' => $aum,

            'aum_date' => $date,

            'nav_per_share' => $nav,

            'nav_date' => $date,

            'shares_outstanding' => $sharesOutstanding,

        ];
    }
}
