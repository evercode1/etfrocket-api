<?php

namespace App\Models;

use Database\Factories\CronLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CronLog extends Model
{
    /** @use HasFactory<CronLogFactory> */
    use HasFactory;

    protected $fillable = [

        'cron_name',
        'status_id',
        'cron_description',
        'cron_fail_details',
        'interval_id',
        'run_time',
        'start_time',
        'end_time',
        'notification_status_id',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
