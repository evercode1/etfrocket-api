<?php

namespace App\Models;

use Database\Factories\EtfMetricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfMetric extends Model
{
    /** @use HasFactory<EtfMetricFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_id',
        'performance_range_type_id',
        'start_date',
        'end_date',
        'start_price',
        'end_price',
        'price_change',
        'price_change_percentage',
        'dividends_paid',
        'dividend_count',
        'average_dividend',
        'total_return_percentage',
        'start_nav',
        'end_nav',
        'nav_change',
        'nav_erosion_percentage',
        'nav_direction_id',
        'start_aum',
        'end_aum',
        'aum_change',
        'aum_change_percentage',
        'aum_direction_id',
        'calculated_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'calculated_at' => 'date:Y-m-d',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',

        ];
    }
}
