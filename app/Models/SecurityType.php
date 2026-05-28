<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityType extends Model
{
    const ETF = 1;

    const STOCK = 2;

    const INDEX = 3;

    const CRYPTO = 4;

    const FOREX = 5;

    const BOND = 6;

    const MUTUAL_FUND = 7;

    const OPTION = 8;

    const FUTURE = 9;

    protected $fillable = [

        'security_type_name',

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

        return self::orderBy('security_type_name', 'asc')

            ->pluck('security_type_name', 'id');
    }
}
