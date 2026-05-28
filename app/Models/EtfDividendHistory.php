<?php

namespace App\Models;

use Database\Factories\EtfDividendHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfDividendHistory extends Model
{
    /** @use HasFactory<EtfDividendHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_id',
        'dividend_amount',
        'ex_dividend_date',
        'payment_date',
        'data_source_id',
        'retrieved_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'ex_dividend_date' => 'date:Y-m-d',
            'payment_date' => 'date:Y-m-d',

        ];
    }
}
