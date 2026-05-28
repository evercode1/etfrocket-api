<?php

namespace App\Models;

use Database\Factories\PortfolioTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioTransaction extends Model
{
    /** @use HasFactory<PortfolioTransactionFactory> */
    use HasFactory;

    protected $fillable = [

        'portfolio_id',
        'security_id',
        'transaction_type_id',
        'shares',
        'price_per_share',
        'transaction_date',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'transaction_date' => 'date:Y-m-d',

        ];

    }
}
