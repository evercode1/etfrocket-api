<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportType extends Model
{
    const AI_DATA_EXTRACTION = 1;

    const MARKET_SNAPSHOT = 2;

    const MARKET_CONDITIONS = 3;

    const MARKET_EVENTS = 4;

    const CALCULATE_SECURITY_METRICS = 5;

    const SECURITY_PRICE_IMPORT = 6;

    const SECURITY_NAV_IMPORT = 7;

    const SECURITY_AUM_IMPORT = 8;

    const SECURITY_DIVIDEND_IMPORT = 9;

    const PORTFOLIO_IMPORT = 10;

    const DATA_INTEGRITY_AUDIT = 11;

    const SCHEDULED_SECURITY_UPDATES = 12;

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
