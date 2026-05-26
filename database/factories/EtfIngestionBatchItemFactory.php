<?php

namespace Database\Factories;

use App\Models\EtfIngestionBatchItem;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class EtfIngestionBatchItemFactory extends Factory
{
    protected $model = EtfIngestionBatchItem::class;

    public function definition(): array
    {
        return [
            'etf_ingestion_batch_id' => 1,
            'etf_id' => 1,
            'status_id' => Status::PENDING,
            'attempts' => 0,
            'runtime_ms' => null,
            'is_processed' => false,
            'is_success' => false,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
