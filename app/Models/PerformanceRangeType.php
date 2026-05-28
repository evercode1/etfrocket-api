<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceRangeType extends Model
{
    const FIVE_DAY = 1;

    const THIRTY_DAY = 2;

    const NINETY_DAY = 3;

    const YEAR_TO_DATE = 4;

    const ONE_YEAR = 5;

    const MAX = 6;

    protected $fillable = [

        'performance_range_type_name',

    ];

    protected function casts(): array
    {
        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];

    }
}
