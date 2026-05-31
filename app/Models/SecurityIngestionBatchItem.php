<?php

namespace App\Models;

use Database\Factories\SecurityIngestionBatchItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityIngestionBatchItem extends Model
{
    /** @use HasFactory<SecurityIngestionBatchItemFactory> */
    use HasFactory;

    protected $fillable = [

        'security_ingestion_batch_id',

        'security_id',

        'status_id',

        'security_update_schedule_id',

        'security_update_type_id',

        'attempts',

        'runtime_ms',

        'is_processed',

        'is_success',

        'error_message',

        'started_at',

        'completed_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
