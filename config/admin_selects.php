<?php

use App\Models\DistributionFrequency;
use App\Models\EtfStrategyType;
use App\Models\ImportType;
use App\Models\Interval;
use App\Models\PerformanceRangeType;
use App\Models\SecurityType;
use App\Models\SecurityUpdateType;
use App\Models\SignalType;
use App\Models\Status;
use App\Models\SupportTopic;
use App\Models\TransactionType;

return [

    'etf_strategy_types' => [

        'key' => 'etf_strategy_types',

        'label' => 'ETF Strategy Types',

        'description' => 'Investment strategy classifications assigned to ETFs.',

        'model' => EtfStrategyType::class,

        'name_column' => 'etf_strategy_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'intervals' => [

        'key' => 'intervals',

        'label' => 'Intervals',

        'description' => 'Time intervals used for market data and charting.',

        'model' => Interval::class,

        'name_column' => 'interval_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'performance_range_types' => [

        'key' => 'performance_range_types',

        'label' => 'Performance Range Types',

        'description' => 'Available time ranges used when calculating performance metrics.',

        'model' => PerformanceRangeType::class,

        'name_column' => 'performance_range_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'signal_types' => [

        'key' => 'signal_types',

        'label' => 'Signal Types',

        'description' => 'AI signal categories available throughout ETF Rocket.',

        'model' => SignalType::class,

        'name_column' => 'signal_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'transaction_types' => [

        'key' => 'transaction_types',

        'label' => 'Transaction Types',

        'description' => 'Portfolio transaction classifications such as Buy and Sell.',

        'model' => TransactionType::class,

        'name_column' => 'transaction_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'import_types' => [

        'key' => 'import_types',

        'label' => 'Import Types',

        'description' => 'Supported methods for importing external data into the platform.',

        'model' => ImportType::class,

        'name_column' => 'import_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => true,

    ],

    'security_types' => [

        'key' => 'security_types',

        'label' => 'Security Types',

        'description' => 'Top-level security classifications such as ETF and Stock.',

        'model' => SecurityType::class,

        'name_column' => 'security_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'security_update_types' => [

        'key' => 'security_update_types',

        'label' => 'Security Update Types',

        'description' => 'Available update schedule categories for securities.',

        'model' => SecurityUpdateType::class,

        'name_column' => 'security_update_type_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'distribution_frequencies' => [

        'key' => 'distribution_frequencies',

        'label' => 'Distribution Frequencies',

        'description' => 'Dividend payment frequencies used by securities.',

        'model' => DistributionFrequency::class,

        'name_column' => 'distribution_frequency_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'statuses' => [

        'key' => 'statuses',

        'label' => 'Statuses',

        'description' => 'System-wide status values used throughout ETF Rocket.',

        'model' => Status::class,

        'name_column' => 'status_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

    'support_topics' => [

        'key' => 'support_topics',

        'label' => 'Support Topics',

        'description' => 'Categories available when users submit support requests.',

        'model' => SupportTopic::class,

        'name_column' => 'support_topic_name',

        'allow_create' => true,

        'allow_update' => true,

        'allow_delete' => false,

    ],

];
