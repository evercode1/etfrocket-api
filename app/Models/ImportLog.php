<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    /** @use HasFactory<\Database\Factories\ImportLogFactory> */
    use HasFactory;

    protected $fillable = [

        'import_type_id',
        'status_id',
        'data_source_id',
        'run_time',
        'rows_processed',
        'records_created',
        'records_updated',
        'duplicate_rows',
        'failure_count',
        'generated_markdown',
        'processing_notes',
        'import_fail_details',
        'passed_data_integrity_check',
        'started_at',
        'completed_at'

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'started_at' => 'datetime:Y-m-d H:i:s',
            'completed_at' => 'datetime:Y-m-d H:i:s',

        ];
    }
}
