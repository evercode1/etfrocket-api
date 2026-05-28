<?php

namespace App\Models;

use Database\Factories\EtfAumHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricDirection extends Model
{
    /** @use HasFactory<EtfAumHistoryFactory> */
    use HasFactory;

    const IMPROVING = 1;

    const ERODING = 2;

    const FLAT = 3;

    const GROWING = 4;

    const SHRINKING = 5;

    protected $fillable = [

        'metric_direction_name',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
