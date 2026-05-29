<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RunAiSecurityPriceExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityPriceExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityPriceExtractionService;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RunAiSecurityPriceExtractionJobTest extends TestCase
{
    private $aiService;

    private $processService;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

        ]);

        $this->aiService =
            Mockery::mock(
                AiSecurityPriceExtractionService::class
            );

        $this->processService =
            Mockery::mock(
                ProcessAiSecurityPriceExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_security_successfully()
    {

        Security::factory()
            ->create();

        $security =
            Security::firstOrFail();

        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'total_securities' => 1,

                ]);

        $batchItem =
            SecurityIngestionBatchItem::factory()
                ->create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                    'status_id' => Status::PENDING,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'security_id' => $security->id,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->with(

                Mockery::on(

                    fn ($passedSecurity) => $passedSecurity->id ===
                        $security->id

                )

            )

            ->andReturn(
                $extraction
            );

        $this->processService
            ->shouldReceive('process')
            ->once()
            ->with(
                $extraction
            );

        $job =
            new RunAiSecurityPriceExtractionJob(

                $batch->id,

                $security->id

            );

        $job->handle(

            $this->aiService,

            $this->processService

        );

        $batch->refresh();

        $batchItem->refresh();

        $this->assertEquals(
            1,
            $batch->processed_count
        );

        $this->assertEquals(
            1,
            $batch->success_count
        );

        $this->assertEquals(
            0,
            $batch->failure_count
        );

        $this->assertEquals(
            Status::COMPLETED,
            $batchItem->status_id
        );

        $this->assertNotNull(
            $batchItem->runtime_ms
        );

        $this->assertNotNull(
            $batchItem->completed_at
        );
    }

    public function test_it_marks_batch_item_as_failed_after_final_attempt()
    {
        Security::factory()
            ->create();

        $security =
            Security::firstOrFail();

        $batch =
            SecurityIngestionBatch::factory()
                ->create([

                    'total_securities' => 1,

                ]);

        $batchItem =
            SecurityIngestionBatchItem::factory()
                ->create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                    'status_id' => Status::PENDING,

                    'attempts' => 2,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->andThrow(

                new \RuntimeException(
                    'AI failed'
                )

            );

        $job = new class($batch->id, $security->id) extends RunAiSecurityPriceExtractionJob
        {
            public function attempts(): int
            {

                return 3;
            }
        };

        try {

            $job->handle(

                $this->aiService,

                $this->processService

            );
        } catch (\Throwable $e) {

            //
        }

        $batch->refresh();

        $batchItem->refresh();

        $this->assertEquals(
            1,
            $batch->processed_count
        );

        $this->assertEquals(
            0,
            $batch->success_count
        );

        $this->assertEquals(
            1,
            $batch->failure_count
        );

        $this->assertEquals(
            Status::FAILED,
            $batchItem->status_id
        );

        $this->assertEquals(
            'AI failed',
            $batchItem->error_message
        );

        $this->assertNotNull(
            $batchItem->completed_at
        );
    }

    public function test_it_resets_to_pending_before_final_attempt()
    {
        Security::factory()
            ->create();

        $security =
            Security::firstOrFail();

        $batch =
            SecurityIngestionBatch::factory()
                ->create();

        $batchItem =
            SecurityIngestionBatchItem::factory()
                ->create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                    'attempts' => 1,

                    'status_id' => Status::PENDING,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->andThrow(

                new \RuntimeException(
                    'Temporary failure'
                )

            );

        $job =
            new RunAiSecurityPriceExtractionJob(

                $batch->id,

                $security->id

            );

        try {

            $job->handle(

                $this->aiService,

                $this->processService

            );
        } catch (\Throwable $e) {

            //
        }

        $batch->refresh();

        $batchItem->refresh();

        /*
        |--------------------------------------------------------------------------
        | Not Counted Yet
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            0,
            $batch->processed_count
        );

        $this->assertEquals(
            0,
            $batch->failure_count
        );

        /*
        |--------------------------------------------------------------------------
        | Returned To Pending
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            Status::PENDING,
            $batchItem->status_id
        );

        $this->assertEquals(
            2,
            $batchItem->attempts
        );
    }

    public function test_it_increments_attempts()
    {
        Security::factory()
            ->create();

        $security =
            Security::firstOrFail();

        $batch =
            SecurityIngestionBatch::factory()
                ->create();

        $batchItem =
            SecurityIngestionBatchItem::factory()
                ->create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                    'attempts' => 2,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'security_id' => $security->id,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                $extraction
            );

        $this->processService
            ->shouldReceive('process')
            ->once();

        $job =
            new RunAiSecurityPriceExtractionJob(

                $batch->id,

                $security->id

            );

        $job->handle(

            $this->aiService,

            $this->processService

        );

        $batchItem->refresh();

        $this->assertEquals(
            3,
            $batchItem->attempts
        );
    }

    public function test_it_sets_processing_status_before_execution()
    {
        Security::factory()
            ->create();

        $security =
            Security::firstOrFail();

        $batch =
            SecurityIngestionBatch::factory()
                ->create();

        $batchItem =
            SecurityIngestionBatchItem::factory()
                ->create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'security_id' => $security->id,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                $extraction
            );

        $this->processService
            ->shouldReceive('process')
            ->once();

        $job =
            new RunAiSecurityPriceExtractionJob(

                $batch->id,

                $security->id

            );

        $job->handle(

            $this->aiService,

            $this->processService

        );

        $batchItem->refresh();

        $this->assertNotNull(
            $batchItem->started_at
        );
    }
}
