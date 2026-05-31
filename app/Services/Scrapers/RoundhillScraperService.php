<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoundhillScraperService
{
    private const DAILY_NAV_URL =

        'https://www.roundhillinvestments.com/assets/data/FilepointRoundhill.40RU.RU_DailyNAV.csv';

    public function extract(
        Security $security
    ): array {

        Log::info(

            'ROUNDHILL SCRAPER STARTED',

            [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

            ]

        );

        $response =

            Http::timeout(60)
                ->get(
                    self::DAILY_NAV_URL
                );

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Failed to retrieve Roundhill fund data.'
            );
        }

        $csv =
            trim(
                $response->body()
            );

        $lines =
            preg_split(
                "/\r\n|\n|\r/",
                $csv
            );

        if (

            empty($lines)

            ||

            count($lines) < 2

        ) {

            throw new \RuntimeException(
                'Roundhill CSV is empty.'
            );
        }

        $headers =

            str_getcsv(
                array_shift(
                    $lines
                ),
                ',',
                '"',
                '\\'
            );

        $row = null;

        foreach (

            $lines as $line

        ) {

            $values =
                str_getcsv(
                    $line,
                    ',',
                    '"',
                    '\\'
                );

            if (

                count($values) !==
                count($headers)

            ) {

                continue;
            }

            $record =

                array_combine(

                    $headers,

                    $values

                );

            if (

                strtoupper(

                    trim(

                        $record['Fund Ticker']
                        ?? ''

                    )

                ) ===

                strtoupper(
                    $security->symbol
                )

            ) {

                $row = $record;

                break;
            }
        }

        if (! $row) {

            throw new \RuntimeException(
                'Roundhill fund data not found for symbol: '.

                $security->symbol

            );
        }

        $aumDate =

            ! empty(
                $row['Rate Date']
            )

            ? Carbon::createFromFormat(

                'm/d/Y',

                trim(
                    $row['Rate Date']
                )

            )->toDateString()

            : null;

        $result = [

            'symbol' => strtoupper(
                $security->symbol
            ),

            'assets_under_management' => isset(
                $row['Net Assets']
            )

                ? (int) round(

                    (float)

                    str_replace(

                        ',',

                        '',

                        $row['Net Assets']

                    )

                )

                : null,

            'aum_date' => $aumDate,

            'nav_per_share' => isset(
                $row['NAV']
            )

                ? round(

                    (float)

                    str_replace(

                        ',',

                        '',

                        $row['NAV']

                    ),

                    4

                )

                : null,

            'nav_date' => $aumDate,

            'shares_outstanding' => isset(
                $row['Shares Outstanding']
            )

                ? (int) round(

                    (float)

                    str_replace(

                        ',',

                        '',

                        $row['Shares Outstanding']

                    )

                )

                : null,

        ];

        if (

            $result['assets_under_management'] === null

            &&

            $result['nav_per_share'] === null

        ) {

            throw new \RuntimeException(
                'Roundhill scraper could not locate fund data.'

            );
        }

        Log::info(

            'ROUNDHILL SCRAPER COMPLETE',

            $result

        );

        return $result;
    }
}
