<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSignalBatchItem extends Model
{
    /** @use HasFactory<\Database\Factories\AiSignalBatchItemFactory> */
    use HasFactory;

    protected $fillable = [

        'ai_signal_batch_id',
        'signal_type_id',
        'import_type_id',
        'status_id',
        'attempts',
        'runtime_ms',
        'is_processed',
        'is_success',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [

        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
