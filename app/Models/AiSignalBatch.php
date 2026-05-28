<?php

namespace App\Models;

use Database\Factories\AiSignalBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSignalBatch extends Model
{
    /** @use HasFactory<AiSignalBatchFactory> */
    use HasFactory;

    protected $fillable = [

        'batch_uuid',
        'status_id',
        'total_signals',
        'processed_count',
        'success_count',
        'failure_count',
        'passed_data_integrity_check',
        'processing_notes',
        'import_fail_details',
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
