<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalType extends Model
{
    /** @use HasFactory<\Database\Factories\SignalTypeFactory> */
    use HasFactory;

    const MARKET_SNAPSHOT = 1;
    const MARKET_CONDITIONS = 2;
    const MARKET_EVENTS = 3;

    protected $fillable = [

        'signal_type_name'

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'

        ];
    }
}
