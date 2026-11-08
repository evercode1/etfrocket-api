<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TappAlpha fund data API
    |--------------------------------------------------------------------------
    |
    | The public fund data endpoint the tappalphafunds.com fund pages load
    | their figures from (the pages ship placeholders and fill them in the
    | browser). Queried as {api_url}?ticker=TDAQ&view=all.
    |
    */

    'tappalpha' => [

        'api_url' => env(
            'TAPPALPHA_FUND_API_URL',
            'https://jdkfnvgkfwotjlyovbrk.supabase.co/functions/v1/fund-public-api'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | NicholasX ETF Page IDs
    |--------------------------------------------------------------------------
    |
    | These values are used to query the NicholasX REST endpoint:
    |
    | /wp-json/twm/v1/data
    |
    | Example:
    |
    | type=daily-nav-table
    | post_id=1916
    |
    */

    'nicholasx' => [

        'page_ids' => [

            'FIAX' => 27,

            'GIAX' => 1665,

            'BLOX' => 1916,

            'GLDN' => 2167,

            'SLVX' => 2182,

            'NUKX' => 2183,

            'WEPN' => 2184,

            'BHDG' => 2365,

            'NGHT' => 2364,

            'FITZ' => 2519,

            'DRMY' => 3000,

        ],

    ],

];
