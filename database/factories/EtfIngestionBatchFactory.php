<?php

namespace Database\Factories;

use App\Models\EtfIngestionBatch;
use App\Models\ImportType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EtfIngestionBatchFactory extends Factory
{
    protected $model =
    EtfIngestionBatch::class;

    public function definition(): array
    {
        return [

            'batch_uuid' => Str::uuid()->toString(),

            'import_type_id' => ImportType::AI_DATA_EXTRACTION,

            'status_id' => Status::PENDING,

            'total_etfs' => 10,

            'processed_count' => 0,

            'success_count' => 0,

            'failure_count' => 0,

            'duplicate_count' => 0,

            'passed_data_integrity_check' => false,

            'processing_notes' => null,

            'import_fail_details' => null,

            'started_at' => now(),

            'completed_at' => null,


        ];
    }
}
