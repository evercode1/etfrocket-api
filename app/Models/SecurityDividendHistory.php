<?php

namespace App\Models;

use Database\Factories\SecurityDividendHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityDividendHistory extends Model
{
    /** @use HasFactory<SecurityDividendHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'security_id',
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
