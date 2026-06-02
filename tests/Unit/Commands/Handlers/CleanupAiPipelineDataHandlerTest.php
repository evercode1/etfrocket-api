<?php

namespace Tests\Unit\Crons\Handlers;

use App\Models\AiDataExtraction;
use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Services\Crons\Handlers\CleanupAiPipelineDataHandler;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupAiPipelineDataHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (

            config(
                'security_pipeline_cleanup.tables'
            ) as $table

        ) {

            DB::table($table)
                ->truncate();
        }
    }

    protected function tearDown(): void
    {
        foreach (

            config(
                'security_pipeline_cleanup.tables'
            ) as $table

        ) {

            DB::table($table)
                ->truncate();
        }

        parent::tearDown();
    }

    public function test_it_deletes_records_older_than_retention_period()
    {
        $oldDate =
            now()->subHours(73);

        $recentDate =
            now()->subHours(24);

        $oldSecurityIngestionBatch =
            SecurityIngestionBatch::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentSecurityIngestionBatch =
            SecurityIngestionBatch::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $oldSecurityIngestionBatchItem =
            SecurityIngestionBatchItem::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentSecurityIngestionBatchItem =
            SecurityIngestionBatchItem::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $oldAiSignalBatch =
            AiSignalBatch::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentAiSignalBatch =
            AiSignalBatch::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $oldAiSignalBatchItem =
            AiSignalBatchItem::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentAiSignalBatchItem =
            AiSignalBatchItem::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $oldExtraction =
            AiDataExtraction::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentExtraction =
            AiDataExtraction::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $oldSignal =
            AiMarketSignal::factory()
                ->create([
                    'created_at' => $oldDate,
                ]);

        $recentSignal =
            AiMarketSignal::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $result =

            app(
                CleanupAiPipelineDataHandler::class
            )->handleCleanupAiPipelineData();

        $this->assertEquals(
            1,
            $result['success']
        );

        $this->assertNull(
            $result['cron_fail_details']
        );

        $this->assertDatabaseMissing(
            'security_ingestion_batches',
            ['id' => $oldSecurityIngestionBatch->id]
        );

        $this->assertDatabaseMissing(
            'security_ingestion_batch_items',
            ['id' => $oldSecurityIngestionBatchItem->id]
        );

        $this->assertDatabaseMissing(
            'ai_signal_batches',
            ['id' => $oldAiSignalBatch->id]
        );

        $this->assertDatabaseMissing(
            'ai_signal_batch_items',
            ['id' => $oldAiSignalBatchItem->id]
        );

        $this->assertDatabaseMissing(
            'ai_data_extractions',
            ['id' => $oldExtraction->id]
        );

        $this->assertDatabaseMissing(
            'ai_market_signals',
            ['id' => $oldSignal->id]
        );

        $this->assertDatabaseHas(
            'security_ingestion_batches',
            ['id' => $recentSecurityIngestionBatch->id]
        );

        $this->assertDatabaseHas(
            'security_ingestion_batch_items',
            ['id' => $recentSecurityIngestionBatchItem->id]
        );

        $this->assertDatabaseHas(
            'ai_signal_batches',
            ['id' => $recentAiSignalBatch->id]
        );

        $this->assertDatabaseHas(
            'ai_signal_batch_items',
            ['id' => $recentAiSignalBatchItem->id]
        );

        $this->assertDatabaseHas(
            'ai_data_extractions',
            ['id' => $recentExtraction->id]
        );

        $this->assertDatabaseHas(
            'ai_market_signals',
            ['id' => $recentSignal->id]
        );
    }

    public function test_it_keeps_records_inside_retention_period()
    {
        $recentDate =
            now()->subHours(24);

        $securityIngestionBatch =
            SecurityIngestionBatch::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $securityIngestionBatchItem =
            SecurityIngestionBatchItem::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $aiSignalBatch =
            AiSignalBatch::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $aiSignalBatchItem =
            AiSignalBatchItem::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $signal =
            AiMarketSignal::factory()
                ->create([
                    'created_at' => $recentDate,
                ]);

        $result =

            app(
                CleanupAiPipelineDataHandler::class
            )->handleCleanupAiPipelineData();

        $this->assertEquals(
            1,
            $result['success']
        );

        $this->assertDatabaseHas(
            'security_ingestion_batches',
            ['id' => $securityIngestionBatch->id]
        );

        $this->assertDatabaseHas(
            'security_ingestion_batch_items',
            ['id' => $securityIngestionBatchItem->id]
        );

        $this->assertDatabaseHas(
            'ai_signal_batches',
            ['id' => $aiSignalBatch->id]
        );

        $this->assertDatabaseHas(
            'ai_signal_batch_items',
            ['id' => $aiSignalBatchItem->id]
        );

        $this->assertDatabaseHas(
            'ai_data_extractions',
            ['id' => $extraction->id]
        );

        $this->assertDatabaseHas(
            'ai_market_signals',
            ['id' => $signal->id]
        );
    }
}
