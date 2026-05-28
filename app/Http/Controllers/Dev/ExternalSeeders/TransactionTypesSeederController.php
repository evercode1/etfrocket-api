<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Models\TransactionType;

class TransactionTypesSeederController
{
    public function run(): void
    {
        TransactionType::truncate();

        TransactionType::updateOrCreate(
            ['id' => TransactionType::BUY],
            [
                'transaction_type_name' => 'Buy',
                'slug' => 'buy',
            ]
        );

        TransactionType::updateOrCreate(
            ['id' => TransactionType::SELL],
            [
                'transaction_type_name' => 'Sell',
                'slug' => 'sell',
            ]
        );
    }
}
