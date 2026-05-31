<?php

namespace App\Models;

use Database\Factories\SecurityDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityDetail extends Model
{
    /** @use HasFactory<SecurityDetailFactory> */
    use HasFactory;

    protected $fillable = [

        'security_id',
        'security_name',
        'etf_issuer_id',
        'etf_strategy_type_id',
        'distribution_frequency_id',
        'expense_ratio',
        'run_day',
        'run_hour',
        'website_url',
        'notes',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
