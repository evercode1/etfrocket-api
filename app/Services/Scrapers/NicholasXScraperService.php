<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NicholasXScraperService
{
    private const API_URL =
        'https://nicholasx.com/wp-json/twm/v1/data';

    public function extract(
        Security $security
    ): array {

        Log::info(

            'NICHOLASX SCRAPER STARTED',

            [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

            ]

        );

        $postId =
            $this->getPostId(
                $security->symbol
            );

        $fundResponse =

            Http::timeout(60)
                ->get(

                    self::API_URL,

                    [

                        'type' => 'daily-nav-table',

                        'post_id' => $postId,

                    ]

                );

        if (! $fundResponse->successful()) {

            throw new \RuntimeException(
                'Failed to retrieve NicholasX fund data.'
            );
        }

        $dateResponse =

            Http::timeout(60)
                ->get(

                    self::API_URL,

                    [

                        'type' => 'date-nav',

                        'post_id' => $postId,

                    ]

                );

        if (! $dateResponse->successful()) {

            throw new \RuntimeException(
                'Failed to retrieve NicholasX date data.'
            );
        }

        $fundHtml =
            $fundResponse->json('html');

        $dateHtml =
            $dateResponse->json('html');

        preg_match(
            '/Net Assets<\/td>\s*<td>\$([0-9.]+)([mb])/i',
            $fundHtml,
            $aumMatches
        );

        preg_match(
            '/NAV<\/td>\s*<td>\$([0-9.]+)/i',
            $fundHtml,
            $navMatches
        );

        preg_match(
            '/Shares Outstanding<\/td>\s*<td>([0-9,]+)/i',
            $fundHtml,
            $sharesMatches
        );

        preg_match(
            '/(\d{2}\/\d{2}\/\d{4})/',
            $dateHtml,
            $dateMatches
        );

        if (

            empty($aumMatches)

            ||

            empty($navMatches)

            ||

            empty($sharesMatches)

            ||

            empty($dateMatches)

        ) {

            throw new \RuntimeException(
                'NicholasX scraper could not locate fund data.'
            );
        }

        $aumValue =
            (float) $aumMatches[1];

        $aumMultiplier =
            strtolower(
                $aumMatches[2]
            ) === 'b'

                ? 1000000000

                : 1000000;

        $date =

            Carbon::createFromFormat(

                'm/d/Y',

                $dateMatches[1]

            )->toDateString();

        $result = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'assets_under_management' => (int) round(
                $aumValue * $aumMultiplier
            ),

            'aum_date' => $date,

            'nav_per_share' => round(

                (float) $navMatches[1],

                4

            ),

            'nav_date' => $date,

            'shares_outstanding' => (int) str_replace(

                ',',

                '',

                $sharesMatches[1]

            ),

        ];

        Log::info(

            'NICHOLASX SCRAPER COMPLETE',

            $result

        );

        return $result;
    }

    private function getPostId(
        string $symbol
    ): int {

        $postId = config(

            'scrapers.nicholasx.page_ids.'.

            strtoupper(
                $symbol
            )

        );

        if (! $postId) {

            throw new \RuntimeException(
                'No NicholasX post ID configured for symbol: '.

                $symbol

            );
        }

        return (int) $postId;
    }
}
