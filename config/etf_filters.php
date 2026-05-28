<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ETF Filters
    |--------------------------------------------------------------------------
    |
    | These filters define the ETF data views available to users.
    | They are grouped by user-facing categories such as Momentum, Stability,
    | Income, and Risk.
    |
    | These are not recommendations or rankings. They are sorting/filtering
    | options that help users explore ETF characteristics.
    |
    */

    'categories' => [

        'momentum' => [

            'display_order' => 1,

            'label' => 'Momentum',

            'description' => 'Find ETFs showing stronger recent performance trends.',

            'filters' => [

                'highest_total_return_percentage' => [
                    'display_order' => 1,
                    'label' => 'Highest Total Return',
                    'description' => 'ETFs with the highest total return, including price movement and dividends.',
                    'column' => 'total_return_percentage',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'highest_price_change_percentage' => [
                    'display_order' => 2,
                    'label' => 'Highest Price Change',
                    'description' => 'ETFs with the highest market price change percentage.',
                    'column' => 'price_change_percentage',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'highest_dividend_count' => [
                    'display_order' => 3,
                    'label' => 'Highest Dividend Count',
                    'description' => 'ETFs with the most dividend payments over the selected period.',
                    'column' => 'dividend_count',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

            ],

        ],

        'stability' => [

            'display_order' => 2,

            'label' => 'Stability',

            'description' => 'Find ETFs with stronger signs of capital preservation and asset stability.',

            'filters' => [

                'lowest_nav_erosion_percentage' => [
                    'display_order' => 1,
                    'label' => 'Lowest NAV Erosion',
                    'description' => 'ETFs with the lowest decline in NAV over the selected period.',
                    'column' => 'nav_erosion_percentage',
                    'sort_direction' => 'asc',
                    'default_range' => '1y',
                ],

                'highest_aum_change_percentage' => [
                    'display_order' => 2,
                    'label' => 'Highest AUM Change',
                    'description' => 'ETFs with the strongest assets under management change percentage.',
                    'column' => 'aum_change_percentage',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'lowest_aum_change_percentage' => [
                    'display_order' => 3,
                    'label' => 'Lowest AUM Change',
                    'description' => 'ETFs with the weakest assets under management change percentage.',
                    'column' => 'aum_change_percentage',
                    'sort_direction' => 'asc',
                    'default_range' => '1y',
                ],

            ],

        ],

        'income' => [

            'display_order' => 3,

            'label' => 'Income',

            'description' => 'Find ETFs with stronger dividend and distribution characteristics.',

            'filters' => [

                'highest_dividends_paid' => [
                    'display_order' => 1,
                    'label' => 'Highest Dividends Paid',
                    'description' => 'ETFs with the highest total dividends paid over the selected period.',
                    'column' => 'dividends_paid',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'highest_dividend_count' => [
                    'display_order' => 2,
                    'label' => 'Highest Dividend Count',
                    'description' => 'ETFs with the most dividend payments over the selected period.',
                    'column' => 'dividend_count',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'highest_average_dividend' => [
                    'display_order' => 3,
                    'label' => 'Highest Average Dividend',
                    'description' => 'ETFs with the highest average dividend payment over the selected period.',
                    'column' => 'average_dividend',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

            ],

        ],

        'risk' => [

            'display_order' => 4,

            'label' => 'Risk',

            'description' => 'Find ETFs with potential warning signs such as NAV erosion, AUM decline, or weak returns.',

            'filters' => [

                'highest_nav_erosion_percentage' => [
                    'display_order' => 1,
                    'label' => 'Highest NAV Erosion',
                    'description' => 'ETFs with the largest decline in NAV over the selected period.',
                    'column' => 'nav_erosion_percentage',
                    'sort_direction' => 'desc',
                    'default_range' => '1y',
                ],

                'lowest_total_return_percentage' => [
                    'display_order' => 2,
                    'label' => 'Lowest Total Return',
                    'description' => 'ETFs with the lowest total return over the selected period.',
                    'column' => 'total_return_percentage',
                    'sort_direction' => 'asc',
                    'default_range' => '1y',
                ],

                'lowest_price_change_percentage' => [
                    'display_order' => 3,
                    'label' => 'Lowest Price Change',
                    'description' => 'ETFs with the lowest market price change percentage over the selected period.',
                    'column' => 'price_change_percentage',
                    'sort_direction' => 'asc',
                    'default_range' => '1y',
                ],

                'lowest_aum_change_percentage' => [
                    'display_order' => 4,
                    'label' => 'Lowest AUM Change',
                    'description' => 'ETFs with the weakest assets under management change percentage.',
                    'column' => 'aum_change_percentage',
                    'sort_direction' => 'asc',
                    'default_range' => '1y',
                ],

            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    'scopes' => [

        'all' => [
            'display_order' => 1,
            'label' => 'All ETFs',
            'description' => 'Search across all tracked ETFs.',
        ],

        'owned' => [
            'display_order' => 2,
            'label' => 'My ETFs',
            'description' => 'Search only ETFs owned in the user portfolio.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Date Ranges
    |--------------------------------------------------------------------------
    */

    'ranges' => [

        'latest' => [
            'display_order' => 1,
            'label' => 'Latest',
            'days' => null,
        ],

        '30d' => [
            'display_order' => 2,
            'label' => '30 Days',
            'days' => 30,
        ],

        '90d' => [
            'display_order' => 3,
            'label' => '90 Days',
            'days' => 90,
        ],

        '1y' => [
            'display_order' => 4,
            'label' => '1 Year',
            'days' => 365,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [

        'category' => 'momentum',

        'filter' => 'highest_total_return_percentage',

        'scope' => 'all',

        'range' => '1y',

        'limit' => 25,

    ],

];
