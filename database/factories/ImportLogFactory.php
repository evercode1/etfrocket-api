<?php

namespace Database\Factories;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportLog>
 */
class ImportLogFactory extends Factory
{
    protected $model =
        ImportLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt =
            now()->subMinutes(
                rand(1, 1440)
            );

        $completedAt =
            (clone $startedAt)->addSeconds(
                rand(1, 30)
            );

        $rowsProcessed =
            rand(100, 5000);

        $recordsCreated =
            rand(0, $rowsProcessed);

        $recordsUpdated =
            rand(0, $rowsProcessed);

        $duplicateRows =
            rand(0, 25);

        $failureCount =
            rand(0, 5);

        return [

            'import_type_id' => rand(1, 4),

            'status_id' => Status::COMPLETED,

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'run_time' => rand(1, 30),

            'rows_processed' => $rowsProcessed,

            'records_created' => $recordsCreated,

            'records_updated' => $recordsUpdated,

            'duplicate_rows' => $duplicateRows,

            'failure_count' => $failureCount,

            'passed_data_integrity_check' => rand(0, 1),

            'generated_markdown' => "# Import Summary\n\nImport completed successfully.",

            'processing_notes' => 'Import pipeline completed without interruption.',

            'import_fail_details' => null,

            'started_at' => $startedAt,

            'completed_at' => $completedAt,

        ];
    }
}
