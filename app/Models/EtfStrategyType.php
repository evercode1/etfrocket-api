<?php

namespace App\Models;

use Database\Factories\EtfFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfStrategyType extends Model
{
    /** @use HasFactory<EtfFactory> */
    use HasFactory;

    const COVERED_CALL = 1;

    const SYNTHETIC_COVERED_CALL = 2;

    const LEVERAGED_COVERED_CALL = 3;

    const SINGLE_STOCK_COVERED_CALL = 4;

    const ZERO_DTE_COVERED_CALL = 5;

    const OPTION_INCOME = 6;

    const LEVERAGED_ETF = 7;

    const INVERSE_ETF = 8;

    const TREASURY_INCOME = 9;

    const DIVIDEND_GROWTH = 10;

    const HIGH_YIELD_INCOME = 11;

    const BUY_WRITE = 12;

    const COLLAR_STRATEGY = 13;

    const BUFFER_ETF = 14;

    const TARGET_INCOME = 15;

    const ALTERNATIVE_INCOME = 16;

    const COLLAR = 17;

    const LEVERAGED = 18;

    const HEDGED_EQUITY = 19;

    const HIGH_YIELD_BOND = 20;

    const CASH_ALTERNATIVE = 21;

    const ENERGY_INFRASTRUCTURE = 22;

    const BOND = 23;

    const CRYPTO_INCOME = 24;

    const PRECIOUS_METALS = 25;

    const DEFENSE = 26;

    const RISK_MANAGED = 27;

    protected $fillable = [

        'etf_strategy_type_name',

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

        return self::orderBy('etf_strategy_type_name', 'asc')

            ->pluck('etf_strategy_type_name', 'id');
    }
}
