<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RunAiEtfPriceExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiEtfPriceExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfPriceExtractionService;
use Database\Seeders\EtfSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RunAiEtfPriceExtractionJobTest extends TestCase
{
    private $aiService;

    private $processService;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_ingestion_batch_items')
            ->truncate();

        DB::table('etf_ingestion_batches')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

            EtfSeeder::class,

        ]);

        $this->aiService =
            Mockery::mock(
                AiEtfPriceExtractionService::class
            );

        $this->processService =
            Mockery::mock(
                ProcessAiEtfPriceExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        DB::table('etf_ingestion_batch_items')
            ->truncate();

        DB::table('etf_ingestion_batches')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_etf_successfully()
    {
        $etf =
            Etf::firstOrFail();

        $batch =
            EtfIngestionBatch::factory()
                ->create([

                    'total_etfs' => 1,

                ]);

        $batchItem =
            EtfIngestionBatchItem::factory()
                ->create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

                    'status_id' => Status::PENDING,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'etf_id' => $etf->id,

                ]);

        $this->aiService
            ->shouldReceive('extract')
            ->once()
            ->with(

                Mockery::on(

                    fn ($passedEtf) => $passedEtf->id ===
                        $etf->id

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
            new RunAiEtfPriceExtractionJob(

                $batch->id,

                $etf->id

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
        $etf =
            Etf::firstOrFail();

        $batch =
            EtfIngestionBatch::factory()
                ->create([

                    'total_etfs' => 1,

                ]);

        $batchItem =
            EtfIngestionBatchItem::factory()
                ->create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

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

        $job = new class($batch->id, $etf->id) extends RunAiEtfPriceExtractionJob
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
        $etf =
            Etf::firstOrFail();

        $batch =
            EtfIngestionBatch::factory()
                ->create();

        $batchItem =
            EtfIngestionBatchItem::factory()
                ->create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

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
            new RunAiEtfPriceExtractionJob(

                $batch->id,

                $etf->id

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
        $etf =
            Etf::firstOrFail();

        $batch =
            EtfIngestionBatch::factory()
                ->create();

        $batchItem =
            EtfIngestionBatchItem::factory()
                ->create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

                    'attempts' => 2,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'etf_id' => $etf->id,

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
            new RunAiEtfPriceExtractionJob(

                $batch->id,

                $etf->id

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
        $etf =
            Etf::firstOrFail();

        $batch =
            EtfIngestionBatch::factory()
                ->create();

        $batchItem =
            EtfIngestionBatchItem::factory()
                ->create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->make([

                    'etf_id' => $etf->id,

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
            new RunAiEtfPriceExtractionJob(

                $batch->id,

                $etf->id

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
