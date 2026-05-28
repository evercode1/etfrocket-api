<?php

namespace App\Models;

use Database\Factories\EtfFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etf extends Model
{
    /** @use HasFactory<EtfFactory> */
    use HasFactory;

    protected $fillable = [

        'symbol',
        'fund_name',
        'etf_issuer_id',
        'etf_strategy_type_id',
        'distribution_frequency_id',
        'status_id',
        'expense_ratio',
        'inception_date',
        'source',
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

    public static function getSelects()
    {

        return self::orderBy('symbol', 'asc')

            ->pluck('symbol', 'id');
    }
}
