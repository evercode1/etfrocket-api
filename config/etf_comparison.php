<?php

return [
    'metrics' => [
        'price' => [
            'label' => 'Price',
            'table' => 'etf_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ],
        'nav' => [
            'label' => 'NAV',
            'table' => 'etf_nav_histories',
            'date_column' => 'nav_date',
            'value_column' => 'nav_per_share',
        ],
        'aum' => [
            'label' => 'AUM',
            'table' => 'etf_aum_histories',
            'date_column' => 'aum_date',
            'value_column' => 'assets_under_management',
        ],
        'dividends' => [
            'label' => 'Dividends',
            'table' => 'etf_dividend_histories',
            'date_column' => 'ex_dividend_date',
            'value_column' => 'dividend_amount',
        ],
    ],

    'ranges' => [
        '5d' => 5,
        '30d' => 30,
        '90d' => 90,
        'ytd' => 'ytd',
        '1y' => 365,
        'max' => 'max',
    ],

    'defaults' => [
        'metric' => 'price',
        'range' => '1y',
        'max_etfs' => 5,
    ],
];
