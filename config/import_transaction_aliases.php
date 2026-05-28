<?php

use App\Models\TransactionType;

return [

    /*
    |--------------------------------------------------------------------------
    | Portfolio Transaction Import Aliases
    |--------------------------------------------------------------------------
    |
    | These aliases allow imported CSV transaction types to map to the
    | correct internal transaction type IDs.
    |
    | All aliases should be lowercase.
    |
    */

    'aliases' => [

        /*
        |--------------------------------------------------------------------------
        | Buy
        |--------------------------------------------------------------------------
        */

        'buy' => TransactionType::BUY,

        'bought' => TransactionType::BUY,

        'purchase' => TransactionType::BUY,

        'purchased' => TransactionType::BUY,

        'add' => TransactionType::BUY,

        'added' => TransactionType::BUY,

        'long' => TransactionType::BUY,

        /*
        |--------------------------------------------------------------------------
        | Sell
        |--------------------------------------------------------------------------
        */

        'sell' => TransactionType::SELL,

        'sold' => TransactionType::SELL,

        'dispose' => TransactionType::SELL,

        'disposed' => TransactionType::SELL,

        'remove' => TransactionType::SELL,

        'removed' => TransactionType::SELL,

        'short' => TransactionType::SELL,

    ],

];
