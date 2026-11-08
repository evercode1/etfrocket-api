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
    private const TABLES = [
        'security_ingestion_batch_items',
        'security_ingestion_batches',
        'ai_data_extractions',
        'ai_signal_batch_items',
        'ai_signal_batches',
        'ai_market_signals',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        parent::tearDown();
    }

    private function runCleanup(): array
    {
        return app(CleanupAiPipelineDataHandler::class)
            ->handleCleanupAiPipelineData();
    }

    public function test_ai_data_extractions_are_kept_for_72_hours(): void
    {
        $old = AiDataExtraction::factory()->create(['created_at' => now()->subHours(73)]);
        $recent = AiDataExtraction::factory()->create(['created_at' => now()->subHours(71)]);

        $result = $this->runCleanup();

        $this->assertSame(1, $result['success']);
        $this->assertNull($result['cron_fail_details']);

        $this->assertDatabaseMissing('ai_data_extractions', ['id' => $old->id]);
        $this->assertDatabaseHas('ai_data_extractions', ['id' => $recent->id]);
    }

    public function test_scrape_batches_and_items_are_kept_for_14_days(): void
    {
        $oldBatch = SecurityIngestionBatch::factory()->create(['created_at' => now()->subDays(15)]);
        $keptBatch = SecurityIngestionBatch::factory()->create(['created_at' => now()->subDays(10)]);

        $oldItem = SecurityIngestionBatchItem::factory()->create(['created_at' => now()->subDays(15)]);
        $keptItem = SecurityIngestionBatchItem::factory()->create(['created_at' => now()->subDays(10)]);

        $this->runCleanup();

        $this->assertDatabaseMissing('security_ingestion_batches', ['id' => $oldBatch->id]);
        $this->assertDatabaseMissing('security_ingestion_batch_items', ['id' => $oldItem->id]);

        // Older than the previous 72-hour retention, still inside 14 days:
        // the admin Pipeline Health page relies on 7 days of history.
        $this->assertDatabaseHas('security_ingestion_batches', ['id' => $keptBatch->id]);
        $this->assertDatabaseHas('security_ingestion_batch_items', ['id' => $keptItem->id]);
    }

    public function test_frozen_ai_signal_data_is_never_deleted(): void
    {
        $signal = AiMarketSignal::factory()->create(['created_at' => now()->subDays(200)]);
        $batch = AiSignalBatch::factory()->create(['created_at' => now()->subDays(200)]);
        $item = AiSignalBatchItem::factory()->create(['created_at' => now()->subDays(200)]);

        $this->runCleanup();

        $this->assertDatabaseHas('ai_market_signals', ['id' => $signal->id]);
        $this->assertDatabaseHas('ai_signal_batches', ['id' => $batch->id]);
        $this->assertDatabaseHas('ai_signal_batch_items', ['id' => $item->id]);
    }
}
