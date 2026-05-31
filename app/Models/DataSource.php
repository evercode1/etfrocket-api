<?php

namespace App\Models;

use Database\Factories\EtfFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    /** @use HasFactory<EtfFactory> */
    use HasFactory;

    const MANUAL_ENTRY = 1;

    const YIELDMAX_WEBSITE = 2;

    const ROUNDHILL_WEBSITE = 3;

    const REX_SHARES_WEBSITE = 4;

    const EODHD_API = 5;

    const TIINGO_API = 6;

    const FINRA = 7;

    const NASDAQ = 8;

    const YAHOO_FINANCE = 9;

    const FMP_API = 10;

    const SEEKING_ALPHA = 11;

    const AI_SCRAPER = 12;

    const TWELVE_DATA_API = 13;

    const WEB_SCRAPER = 14;

    protected $fillable = [

        'data_source_name',
        'website_url',
        'status_id',
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
