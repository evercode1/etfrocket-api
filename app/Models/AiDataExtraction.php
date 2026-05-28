<?php

namespace App\Models;

use Database\Factories\AiDataExtractionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiDataExtraction extends Model
{
    /** @use HasFactory<AiDataExtractionFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_id',
        'data_source_id',
        'source_url',
        'raw_payload',
        'prompt',
        'extracted_data',
        'is_validated',
        'validation_notes',
        'processed_at',
        'failed_at',
        'failure_reason',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'extracted_data' => 'array',
            'is_validated' => 'boolean',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
