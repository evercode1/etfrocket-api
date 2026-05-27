<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\GenerateAiSignalJob;
use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\ImportType;
use App\Models\SignalType;
use App\Models\Status;
use App\Services\AI\AiSignals\IsMarketOpenService;
use App\Services\Crons\Handlers\GenerateAiSignalsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\SignalTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GenerateAiSignalsHandlerTest extends TestCase
{
    private GenerateAiSignalsHandler
        $handler;

    private $marketOpenService;

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

        $this->marketOpenService =
            Mockery::mock(
                IsMarketOpenService::class
            );

        $this->handler =
            new GenerateAiSignalsHandler(

                $this->marketOpenService

            );
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

    public function test_it_dispatches_all_signal_jobs()
    {
        $this->marketOpenService
            ->shouldReceive('isOpen')
            ->once()
            ->andReturn(true);

        $results =
            $this->handler
            ->handleGenerateAiSignals();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            GenerateAiSignalJob::class,
            3
        );

        $this->assertDatabaseCount(
            'ai_signal_batches',
            1
        );

        $this->assertDatabaseCount(
            'ai_signal_batch_items',
            3
        );
    }

    public function test_it_creates_batch_record()
    {
        $this->marketOpenService
            ->shouldReceive('isOpen')
            ->once()
            ->andReturn(true);

        $this->handler
            ->handleGenerateAiSignals();

        $this->assertDatabaseHas(

            'ai_signal_batches',

            [

                'status_id' =>
                Status::PENDING,

                'total_signals' => 3,

                'processed_count' => 0,

                'success_count' => 0,

                'failure_count' => 0,

            ]

        );
    }

    public function test_it_creates_batch_items()
    {
        $this->marketOpenService
            ->shouldReceive('isOpen')
            ->once()
            ->andReturn(true);

        $this->handler
            ->handleGenerateAiSignals();

        $batch =
            AiSignalBatch::first();

        $this->assertNotNull(
            $batch
        );

        $this->assertEquals(
            3,
            AiSignalBatchItem::count()
        );

        $this->assertDatabaseHas(

            'ai_signal_batch_items',

            [

                'ai_signal_batch_id' =>
                $batch->id,

                'signal_type_id' =>
                SignalType::MARKET_SNAPSHOT,

                'import_type_id' =>
                ImportType::MARKET_SNAPSHOT,

                'status_id' =>
                Status::PENDING,

                'is_processed' => 0,

                'is_success' => 0,

            ]

        );
    }

    public function test_it_skips_when_market_is_closed()
    {
        $this->marketOpenService
            ->shouldReceive('isOpen')
            ->once()
            ->andReturn(false);

        $results =
            $this->handler
            ->handleGenerateAiSignals();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'ai_signal_batches',
            0
        );
    }

    public function test_it_skips_when_signals_are_fresh()
    {
        $this->marketOpenService
            ->shouldReceive('isOpen')
            ->once()
            ->andReturn(true);

        AiMarketSignal::create([

            'signal_type_id' =>
            SignalType::MARKET_SNAPSHOT,

            'title' =>
            'Test Signal',

            'subtitle' =>
            'Test Subtitle',

            'market_mood' =>
            'Bullish',

            'confidence_score' =>
            88,

            'markdown_content' =>
            '# Test',

            'payload_json' => [],

            'generated_at' =>
            now(),

            'expires_at' =>
            now()->addDay(),

            'is_active' => true,

            'ai_model' =>
            'gpt-4.1-mini',

        ]);

        $results =
            $this->handler
            ->handleGenerateAiSignals();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'ai_signal_batches',
            0
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {


        AiMarketSignal::create([

            'signal_type_id' =>
            SignalType::MARKET_SNAPSHOT,

            'title' =>
            'Test Signal',

            'subtitle' =>
            'Test Subtitle',

            'market_mood' =>
            'Bullish',

            'confidence_score' =>
            88,

            'markdown_content' =>
            '# Test',

            'payload_json' => [],

            'generated_at' =>
            now(),

            'expires_at' =>
            now()->addDay(),

            'is_active' => true,

            'ai_model' =>
            'gpt-4.1-mini',

        ]);

        $results =
            $this->handler
            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            GenerateAiSignalJob::class,
            3
        );

        $this->assertDatabaseCount(
            'ai_signal_batches',
            1
        );
    }
}
