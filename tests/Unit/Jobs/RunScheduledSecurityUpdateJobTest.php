<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\AiDataExtraction;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityAumExtractionService;
use App\Services\AI\Extractions\AiSecurityDividendExtractionService;
use App\Services\AI\Extractions\AiSecurityNavExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityAumExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityDividendExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityNavExtractionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunScheduledSecurityUpdateJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_update_schedules')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_update_schedules')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_dividend_update_successfully()
    {
        $schedule =

            SecurityUpdateSchedule::factory()

                ->dividend()

                ->create();

        $batch =

            SecurityIngestionBatch::create([

                'batch_uuid' => 'test-batch',

                'import_type_id' => 1,

                'status_id' => Status::PENDING,

                'total_securities' => 1,

                'processed_count' => 0,

                'success_count' => 0,

                'failure_count' => 0,

                'duplicate_count' => 0,

                'passed_data_integrity_check' => false,

                'processing_notes' => 'test',

                'started_at' => now(),

            ]);

        $batchItem =

            SecurityIngestionBatchItem::create([

                'security_ingestion_batch_id' => $batch->id,

                'security_update_schedule_id' => $schedule->id,

                'security_id' => $schedule->security_id,

                'security_update_type_id' => SecurityUpdateType::DIVIDEND,

                'status_id' => Status::PENDING,

                'attempts' => 0,

                'is_processed' => false,

                'is_success' => false,

            ]);

        $extraction =

            AiDataExtraction::factory()
                ->create([
                    'security_id' => $schedule->security_id,
                ]);

        $extractService =

            $this->createMock(
                AiSecurityDividendExtractionService::class
            );

        $extractService
            ->expects($this->once())
            ->method('extract')
            ->willReturn($extraction);

        $processService =

            $this->createMock(
                ProcessAiSecurityDividendExtractionService::class
            );

        $processService
            ->expects($this->once())
            ->method('process')
            ->willReturn($extraction);

        $job =

            new RunScheduledSecurityUpdateJob(

                $batch->id,

                $schedule->id

            );

        $job->handle(

            $extractService,

            $processService,

            $this->createMock(
                AiSecurityAumExtractionService::class
            ),

            $this->createMock(
                ProcessAiSecurityAumExtractionService::class
            ),

            $this->createMock(
                AiSecurityNavExtractionService::class
            ),

            $this->createMock(
                ProcessAiSecurityNavExtractionService::class
            )

        );

        $batchItem->refresh();

        $batch->refresh();

        $this->assertEquals(
            Status::COMPLETED,
            $batchItem->status_id
        );

        $this->assertEquals(
            1,
            $batchItem->is_processed
        );

        $this->assertEquals(
            1,
            $batchItem->is_success
        );

        $this->assertEquals(
            1,
            $batch->processed_count
        );

        $this->assertEquals(
            1,
            $batch->success_count
        );
    }
}
