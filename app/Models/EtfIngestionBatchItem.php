<?php

namespace App\Models;

use Database\Factories\EtfIngestionBatchItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfIngestionBatchItem extends Model
{
    /** @use HasFactory<EtfIngestionBatchItemFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_ingestion_batch_id',

        'etf_id',

        'status_id',

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
