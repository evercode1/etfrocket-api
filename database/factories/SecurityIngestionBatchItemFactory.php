<?php

namespace Database\Factories;

use App\Models\SecurityIngestionBatchItem;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityIngestionBatchItemFactory extends Factory
{
    protected $model = SecurityIngestionBatchItem::class;

    public function definition(): array
    {
        return [
            'security_ingestion_batch_id' => 1,
            'security_id' => 1,
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
