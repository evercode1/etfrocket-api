<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FinalizeAiSignalBatchJob;
use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\SignalType;
use App\Models\Status;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\SignalTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalizeAiSignalBatchJobTest extends TestCase
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

        DB::table('import_logs')
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
    }

    protected function tearDown(): void
    {
        DB::table('ai_signal_batch_items')
            ->truncate();

        DB::table('ai_signal_batches')
            ->truncate();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('import_logs')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_finalizes_batch_successfully()
    {
        $batch =
            AiSignalBatch::factory()
            ->create([

                'total_signals' => 1,

                'processed_count' => 1,

                'success_count' => 1,

                'failure_count' => 0,

            ]);

        AiSignalBatchItem::factory()
            ->create([

                'ai_signal_batch_id' =>
                $batch->id,

                'status_id' =>
                Status::COMPLETED,

                'is_processed' => true,

                'is_success' => true,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>
                SignalType::MARKET_SNAPSHOT,

            ]);

        $job =
            new FinalizeAiSignalBatchJob(
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
    }

    public function test_it_marks_batch_failed_when_failures_exist()
    {
        $batch =
            AiSignalBatch::factory()
            ->create([

                'total_signals' => 1,

                'processed_count' => 1,

                'success_count' => 0,

                'failure_count' => 1,

            ]);

        AiSignalBatchItem::factory()
            ->create([

                'ai_signal_batch_id' =>
                $batch->id,

                'status_id' =>
                Status::FAILED,

                'is_processed' => true,

                'is_success' => false,

                'error_message' =>
                'Test failure',

            ]);

        $job =
            new FinalizeAiSignalBatchJob(
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

        $this->assertDatabaseCount(
            'import_logs',
            1
        );
    }
}
