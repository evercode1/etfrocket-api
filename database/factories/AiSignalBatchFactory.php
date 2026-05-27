<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiSignalBatchFactory extends Factory
{
    public function definition(): array
    {
        return [

            'batch_uuid' =>

            Str::uuid()->toString(),

            'status_id' =>

            Status::PENDING,

            'total_signals' => 3,

            'processed_count' => 0,

            'success_count' => 0,

            'failure_count' => 0,

            'passed_data_integrity_check' => false,

            'processing_notes' =>
            'AI signal batch queued.',

            'import_fail_details' => null,

            'started_at' => now(),

            'completed_at' => null,

        ];
    }
}
