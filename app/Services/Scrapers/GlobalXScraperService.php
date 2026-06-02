<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GlobalXScraperService
{
    public function extract(
        Security $security
    ): array {

        $symbol = strtoupper(
            $security->symbol
        );

        $url = sprintf(
            'https://www.globalxetfs.com/funds/%s',
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
                'Unable to retrieve Global X ETF page.'
            );
        }

        $html = $response->body();

        preg_match(
            '/\\\\\\"ASSETS\\\\\\":([0-9\.]+)/',
            $html,
            $aumMatch
        );

        preg_match(
            '/\\\\\\"NET_ASSET_VALUE\\\\\\":\\\\\\"([0-9\.]+)\\\\\\"/',
            $html,
            $navMatch
        );

        preg_match(
            '/\\\\\\"SHARES_OUTSTANDING\\\\\\":([0-9]+)/',
            $html,
            $sharesMatch
        );

        preg_match(
            '/\\\\\"AS_OF_DATE\\\\\":\\\\\"\$D([0-9]{4}-[0-9]{2}-[0-9]{2})/',
            $html,
            $dateMatch
        );

        $aum = isset($aumMatch[1])
            ? (float) $aumMatch[1]
            : null;

        $nav = isset($navMatch[1])
            ? (float) $navMatch[1]
            : null;

        $sharesOutstanding = isset($sharesMatch[1])
            ? (int) $sharesMatch[1]
            : null;

        $date = isset($dateMatch[1])
            ? Carbon::parse($dateMatch[1])->toDateString()
            : null;

        if (
            $aum === null ||
            $nav === null
        ) {

            throw new \RuntimeException(
                'Global X scraper could not locate fund data.'
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
