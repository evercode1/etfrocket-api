<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FinalizeSecurityPriceExtractionBatchJob;
use App\Models\ImportType;
use App\Models\SecurityIngestionBatch;
use App\Models\Status;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalizeSecurityPriceExtractionBatchJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('import_logs')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('import_logs')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_finalizes_successful_batch()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_securities' => 10,

                    'processed_count' => 10,

                    'success_count' => 10,

                    'failure_count' => 0,

                    'duplicate_count' => 0,

                    'started_at' => now()->subMinutes(5),

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
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

        $this->assertDatabaseCount(
            'import_logs',
            1
        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                'status_id' => Status::COMPLETED,

                'rows_processed' => 10,

                'records_created' => 10,

                'failure_count' => 0,

                'passed_data_integrity_check' => 1,

            ]

        );
    }

    public function test_it_finalizes_failed_batch()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_securities' => 10,

                    'processed_count' => 10,

                    'success_count' => 8,

                    'failure_count' => 2,

                    'duplicate_count' => 0,

                    'started_at' => now()->subMinutes(5),

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertEquals(
            Status::FAILED,
            $batch->status_id
        );

        /*
        |--------------------------------------------------------------------------
        | Integrity Should Fail
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            0,
            $batch->passed_data_integrity_check
        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'status_id' => Status::FAILED,

                'rows_processed' => 10,

                'records_created' => 8,

                'failure_count' => 2,

                'passed_data_integrity_check' => 0,

            ]

        );
    }

    public function test_it_sets_completed_at()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'processed_count' => 5,

                    'total_securities' => 5,

                    'success_count' => 5,

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertNotNull(
            $batch->completed_at
        );
    }

    public function test_it_creates_import_log()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'processed_count' => 3,

                    'total_securities' => 3,

                    'success_count' => 3,

                    'failure_count' => 0,

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
                $batch->id
            );

        $job->handle();

        $this->assertDatabaseCount(
            'import_logs',
            1
        );
    }

    public function test_it_sets_processing_notes_for_failed_batch()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'processed_count' => 4,

                    'total_securities' => 4,

                    'success_count' => 2,

                    'failure_count' => 2,

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertEquals(

            'AI security price extraction batch completed with failures.',

            $batch->processing_notes

        );
    }

    public function test_it_sets_processing_notes_for_successful_batch()
    {
        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'processed_count' => 4,

                    'total_securities' => 4,

                    'success_count' => 4,

                    'failure_count' => 0,

                ]);

        $job =
            new FinalizeSecurityPriceExtractionBatchJob(
                $batch->id
            );

        $job->handle();

        $batch->refresh();

        $this->assertEquals(

            'AI security price extraction batch completed successfully.',

            $batch->processing_notes

        );
    }
}
