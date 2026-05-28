<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportType extends Model
{
    public const AI_DATA_EXTRACTION = 1;

    public const MARKET_SNAPSHOT = 2;

    public const MARKET_CONDITIONS = 3;

    public const MARKET_EVENTS = 4;

    public const CALCULATE_ETF_METRICS = 5;

    public const ETF_PRICE_IMPORT = 6;

    public const ETF_NAV_IMPORT = 7;

    public const ETF_AUM_IMPORT = 8;

    public const ETF_DIVIDEND_IMPORT = 9;

    public const PORTFOLIO_IMPORT = 10;

    public const DATA_INTEGRITY_AUDIT = 11;

    protected $fillable = [

        'import_type_name',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
