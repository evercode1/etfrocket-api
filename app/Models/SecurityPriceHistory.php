<?php

namespace App\Models;

use Database\Factories\SecurityPriceHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityPriceHistory extends Model
{
    /** @use HasFactory<SecurityPriceHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'security_id',
        'price_date',
        'close_price',
        'volume',
        'data_source_id',
        'retrieved_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'price_date' => 'date:Y-m-d',
            'retrieved_at' => 'date:Y-m-d',

        ];
    }
}
