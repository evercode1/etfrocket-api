<?php

return [

    'metrics' => [

        'price_growth' => [

            'label' => 'Price Growth',

            'metric_column' =>
            'price_change_percentage',

            'sort_direction' => 'desc',

            'format' => 'percent',

            'supports_chart' => true,

            'supports_explorer' => true,

        ],

        'forward_yield' => [

            'label' => 'Forward Yield',

            'metric_column' =>
            'dividends_paid',

            'sort_direction' => 'desc',

            'format' => 'currency',

            'supports_chart' => true,

            'supports_explorer' => true,

        ],

        'nav_stability' => [

            'label' => 'NAV Stability',

            'metric_column' =>
            'nav_erosion_percentage',

            'sort_direction' => 'desc',

            'format' => 'percent',

            'supports_chart' => true,

            'supports_explorer' => true,

        ],

        'aum_growth' => [

            'label' => 'AUM Growth',

            'metric_column' =>
            'aum_change_percentage',

            'sort_direction' => 'desc',

            'format' => 'percent',

            'supports_chart' => true,

            'supports_explorer' => true,

        ],

        'total_return' => [

            'label' => 'Total Return',

            'metric_column' =>
            'total_return_percentage',

            'sort_direction' => 'desc',

            'format' => 'percent',

            'supports_chart' => true,

            'supports_explorer' => true,

        ],

    ],

];
