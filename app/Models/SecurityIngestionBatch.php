<?php

namespace App\Models;

use Database\Factories\SecurityIngestionBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityIngestionBatch extends Model
{
    /** @use HasFactory<SecurityIngestionBatchFactory> */
    use HasFactory;

    protected $fillable = [

        'batch_uuid',

        'import_type_id',

        'status_id',

        'total_securities',

        'processed_count',

        'success_count',

        'failure_count',

        'duplicate_count',

        'passed_data_integrity_check',

        'processing_notes',

        'import_fail_details',

        'started_at',

        'completed_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',

        ];
    }
}
