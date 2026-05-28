<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FinalizeAiSignalBatchJob;
use App\Jobs\GenerateAiSignalJob;
use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\ImportType;
use App\Models\SignalType;
use App\Models\Status;
use App\Services\AI\AiSignals\GenerateAiSignalService;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\SignalTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GenerateAiSignalJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_signal_batch_items')
            ->truncate();

        DB::table('ai_signal_batches')
            ->truncate();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

            SignalTypeSeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        DB::table('ai_signal_batch_items')
            ->truncate();

        DB::table('ai_signal_batches')
            ->truncate();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_signal_successfully()
    {
        $batch =
            AiSignalBatch::factory()
                ->create([

                    'total_signals' => 1,

                ]);

        $batchItem =
            AiSignalBatchItem::factory()
                ->create([

                    'ai_signal_batch_id' => $batch->id,

                    'signal_type_id' => SignalType::MARKET_SNAPSHOT,

                    'import_type_id' => ImportType::MARKET_SNAPSHOT,

                ]);

        $signal =
            AiMarketSignal::factory()
                ->make();

        $service =
            Mockery::mock(
                GenerateAiSignalService::class
            );

        $service->shouldReceive('generate')
            ->once()
            ->andReturn($signal);

        $job =
            new GenerateAiSignalJob(

                $batch->id,

                SignalType::MARKET_SNAPSHOT,

                ImportType::MARKET_SNAPSHOT

            );

        $job->handle($service);

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

    public function test_it_marks_batch_item_failed_after_final_attempt()
    {
        $batch =
            AiSignalBatch::factory()
                ->create([

                    'total_signals' => 1,

                ]);

        $batchItem =
            AiSignalBatchItem::factory()
                ->create([

                    'ai_signal_batch_id' => $batch->id,

                ]);

        $service =
            Mockery::mock(
                GenerateAiSignalService::class
            );

        $service->shouldReceive('generate')
            ->once()
            ->andThrow(
                new \Exception('Failure')
            );

        /** @var GenerateAiSignalJob|MockInterface $job */
        $job =
            Mockery::mock(

                GenerateAiSignalJob::class,

                [

                    $batch->id,

                    SignalType::MARKET_SNAPSHOT,

                    ImportType::MARKET_SNAPSHOT,

                ]

            )
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

        $job->shouldReceive('attempts')
            ->andReturn(3);

        try {

            $job->handle($service);
        } catch (\Throwable $e) {

            //
        }

        $batchItem->refresh();

        $batch->refresh();

        $this->assertEquals(
            Status::FAILED,
            $batchItem->status_id
        );

        $this->assertEquals(
            1,
            $batchItem->is_processed
        );

        $this->assertEquals(
            0,
            $batchItem->is_success
        );

        $this->assertEquals(
            1,
            $batch->failure_count
        );
    }

    public function test_it_dispatches_finalizer_when_batch_complete()
    {
        $batch =
            AiSignalBatch::factory()
                ->create([

                    'total_signals' => 1,

                ]);

        AiSignalBatchItem::factory()
            ->create([

                'ai_signal_batch_id' => $batch->id,

            ]);

        $signal =
            AiMarketSignal::factory()
                ->make();

        $service =
            Mockery::mock(
                GenerateAiSignalService::class
            );

        $service->shouldReceive('generate')
            ->once()
            ->andReturn($signal);

        $job =
            new GenerateAiSignalJob(

                $batch->id,

                SignalType::MARKET_SNAPSHOT,

                ImportType::MARKET_SNAPSHOT

            );

        $job->handle($service);

        Queue::assertPushed(
            FinalizeAiSignalBatchJob::class
        );
    }
}
