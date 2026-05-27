<?php

namespace Database\Factories;

use App\Models\AiSignalBatch;
use App\Models\ImportType;
use App\Models\SignalType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiSignalBatchItemFactory extends Factory
{
    public function definition(): array
    {
        return [

            'ai_signal_batch_id' =>

            1,

            'signal_type_id' =>

            SignalType::MARKET_SNAPSHOT,

            'import_type_id' =>

            ImportType::MARKET_SNAPSHOT,

            'status_id' =>

            Status::PENDING,

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
