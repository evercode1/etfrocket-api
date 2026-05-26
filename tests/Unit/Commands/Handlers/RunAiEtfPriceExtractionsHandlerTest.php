<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiEtfPriceExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\EtfPriceHistory;
use App\Models\ImportType;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiEtfPriceExtractionsHandler;
use Database\Seeders\EtfSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiEtfPriceExtractionsHandlerTest extends TestCase
{
    private RunAiEtfPriceExtractionsHandler
        $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_ingestion_batch_items')
            ->truncate();

        DB::table('etf_ingestion_batches')
            ->truncate();

        DB::table('etf_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

            EtfSeeder::class,

        ]);

        Queue::fake();

        $this->handler =
            app(
                RunAiEtfPriceExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_ingestion_batch_items')
            ->truncate();

        DB::table('etf_ingestion_batches')
            ->truncate();

        DB::table('etf_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_etfs()
    {
        $etfCount =
            Etf::count();

        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiEtfPriceExtractionJob::class,
            $etfCount
        );

        $this->assertDatabaseCount(
            'etf_ingestion_batches',
            1
        );

        $this->assertDatabaseCount(
            'etf_ingestion_batch_items',
            $etfCount
        );
    }

    public function test_it_creates_batch_record()
    {
        $this->handler
            ->handleRunAiEtfPriceExtractions([

                'limit' => 2,

            ]);

        $this->assertDatabaseHas(

            'etf_ingestion_batches',

            [

                'import_type_id' =>
                ImportType::AI_DATA_EXTRACTION,

                'status_id' =>
                Status::PENDING,

                'total_etfs' => 2,

                'processed_count' => 0,

                'success_count' => 0,

                'failure_count' => 0,

            ]

        );
    }

    public function test_it_creates_batch_items()
    {
        $this->handler
            ->handleRunAiEtfPriceExtractions([

                'limit' => 3,

            ]);

        $batch =
            EtfIngestionBatch::first();

        $this->assertNotNull(
            $batch
        );

        $this->assertEquals(
            3,
            EtfIngestionBatchItem::count()
        );

        $this->assertDatabaseHas(

            'etf_ingestion_batch_items',

            [

                'etf_ingestion_batch_id' =>
                $batch->id,

                'status_id' =>
                Status::PENDING,

                'is_processed' => 0,

                'is_success' => 0,

            ]

        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions([

                'symbol' => 'CHPY',

            ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        $batch =
            EtfIngestionBatch::first();

        $this->assertEquals(
            1,
            $batch->total_etfs
        );

        Queue::assertPushed(

            RunAiEtfPriceExtractionJob::class,

            function ($job) use ($etf, $batch) {

                return

                    $job->etfId ===
                    $etf->id

                    &&

                    $job->batchId ===
                    $batch->id;
            }

        );
    }

    public function test_it_respects_limit()
    {
        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions([

                'limit' => 5,

            ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiEtfPriceExtractionJob::class,
            5
        );

        $this->assertDatabaseHas(

            'etf_ingestion_batches',

            [

                'total_etfs' => 5,

            ]

        );
    }

    public function test_it_skips_when_price_data_is_not_fresh()
    {
        EtfPriceHistory::create([

            'etf_id' =>
            Etf::first()->id,

            'price_date' =>
            now()->toDateString(),

            'close_price' =>
            25.44,

            'volume' =>
            100000,

            'data_source_id' => 1,

            'retrieved_at' =>
            now(),

        ]);

        AiDataExtraction::factory()
            ->create([

                'created_at' =>
                now(),

            ]);

        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'etf_ingestion_batches',
            0
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {
        EtfPriceHistory::create([

            'etf_id' =>
            Etf::first()->id,

            'price_date' =>
            now()->toDateString(),

            'close_price' =>
            25.44,

            'volume' =>
            100000,

            'data_source_id' => 1,

            'retrieved_at' =>
            now(),

        ]);

        AiDataExtraction::factory()
            ->create([

                'created_at' =>
                now(),

            ]);

        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions([

                'force' => true,

                'limit' => 2,

            ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiEtfPriceExtractionJob::class,
            2
        );

        $this->assertDatabaseCount(
            'etf_ingestion_batches',
            1
        );
    }

    public function test_it_returns_success_when_no_etfs_exist()
    {
        DB::table('etfs')
            ->truncate();

        $results =
            $this->handler
            ->handleRunAiEtfPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'etf_ingestion_batches',
            0
        );
    }
}
