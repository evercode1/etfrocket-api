<?php

namespace App\Models;

use Database\Factories\EtfPriceHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfPriceHistory extends Model
{
    /** @use HasFactory<EtfPriceHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_id',
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
