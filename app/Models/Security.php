<?php

namespace App\Models;

use Database\Factories\SecurityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Security extends Model
{
    /** @use HasFactory<SecurityFactory> */
    use HasFactory;

    protected $fillable = [

        'symbol',
        'security_type_id',
        'status_id',

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
