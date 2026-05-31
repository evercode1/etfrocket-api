<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FinalizeScheduledUpdatesBatchJob;
use App\Models\ImportType;
use App\Models\SecurityIngestionBatch;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalizeScheduledUpdatesBatchJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batches')
            ->truncate();
        DB::table('import_logs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batches')
            ->truncate();
        DB::table('import_logs')->truncate();

        parent::tearDown();
    }

    public function test_it_finalizes_successful_batch()
    {
        $batch =

            SecurityIngestionBatch::create([

                'batch_uuid' => 'success-batch',

                'import_type_id' => ImportType::SCHEDULED_SECURITY_UPDATES,

                'status_id' => Status::PROCESSING,

                'total_securities' => 2,

                'processed_count' => 2,

                'success_count' => 2,

                'failure_count' => 0,

                'duplicate_count' => 0,

                'passed_data_integrity_check' => false,

                'processing_notes' => null,

                'started_at' => now()->subMinutes(5),

            ]);

        $job =

            new FinalizeScheduledUpdatesBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertEquals(
            Status::COMPLETED,
            $batch->status_id
        );

        $this->assertEquals(
            1,
            $batch->passed_data_integrity_check
        );

        $this->assertNotNull(
            $batch->completed_at
        );

        $this->assertEquals(

            'Scheduled security update batch completed successfully.',

            $batch->processing_notes

        );
    }

    public function test_it_finalizes_failed_batch()
    {
        $batch =

            SecurityIngestionBatch::create([

                'batch_uuid' => 'failed-batch',

                'import_type_id' => ImportType::SCHEDULED_SECURITY_UPDATES,

                'status_id' => Status::PROCESSING,

                'total_securities' => 2,

                'processed_count' => 2,

                'success_count' => 1,

                'failure_count' => 1,

                'duplicate_count' => 0,

                'passed_data_integrity_check' => false,

                'processing_notes' => null,

                'import_fail_details' => 'Test failure',

                'started_at' => now()->subMinutes(5),

            ]);

        $job =

            new FinalizeScheduledUpdatesBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertEquals(
            Status::FAILED,
            $batch->status_id
        );

        $this->assertEquals(

            0,

            $batch->passed_data_integrity_check

        );

        $this->assertNotNull(
            $batch->completed_at
        );

        $this->assertEquals(

            'Scheduled security update batch completed with failures.',

            $batch->processing_notes

        );
    }
}
