<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfIngestionBatch extends Model
{
    /** @use HasFactory<\Database\Factories\EtfIngestionBatchFactory> */
    use HasFactory;

    protected $fillable = [

        'batch_uuid',

        'import_type_id',

        'status_id',

        'total_etfs',

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
