<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiSecurityPriceExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiSecurityPriceExtractionsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityPriceExtractionsHandlerTest extends TestCase
{
    private RunAiSecurityPriceExtractionsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

        ]);

        Queue::fake();

        $this->handler =
            app(
                RunAiSecurityPriceExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {

        Security::factory()
            ->count(5)
            ->create();

        $securityCount =
            Security::count();

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            $securityCount
        );

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            1
        );

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            $securityCount
        );
    }

    public function test_it_creates_batch_record()
    {

        Security::factory()
            ->count(2)
            ->create();

        $this->handler
            ->handleRunAiSecurityPriceExtractions([

                'limit' => 2,

            ]);

        $this->assertDatabaseHas(

            'security_ingestion_batches',

            [

                'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                'status_id' => Status::PENDING,

                'total_securities' => 2,

                'processed_count' => 0,

                'success_count' => 0,

                'failure_count' => 0,

            ]

        );
    }

    public function test_it_creates_batch_items()
    {

        Security::factory()
            ->count(5)
            ->create();

        $this->handler
            ->handleRunAiSecurityPriceExtractions([

                'limit' => 3,

            ]);

        $batch =
            SecurityIngestionBatch::first();

        $this->assertNotNull(
            $batch
        );

        $this->assertEquals(
            3,
            SecurityIngestionBatchItem::count()
        );

        $this->assertDatabaseHas(

            'security_ingestion_batch_items',

            [

                'security_ingestion_batch_id' => $batch->id,

                'status_id' => Status::PENDING,

                'is_processed' => 0,

                'is_success' => 0,

            ]

        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {

        Security::create([
            'symbol' => 'CHPY',

            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions([

                    'symbol' => 'CHPY',

                ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        $batch =
            SecurityIngestionBatch::first();

        $this->assertEquals(
            1,
            $batch->total_securities
        );

        Queue::assertPushed(

            RunAiSecurityPriceExtractionJob::class,

            function ($job) use ($security, $batch) {

                return

                    $job->securityId ===
                    $security->id

                    &&

                    $job->batchId ===
                    $batch->id;
            }

        );
    }

    public function test_it_respects_limit()
    {

        Security::factory()
            ->count(10)
            ->create();

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions([

                    'limit' => 5,

                ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            5
        );

        $this->assertDatabaseHas(

            'security_ingestion_batches',

            [

                'total_securities' => 5,

            ]

        );
    }

    public function test_it_skips_when_all_active_securities_have_fresh_price_data()
    {

        Security::factory()
            ->count(3)
            ->create();
        $today =
            now()->toDateString();

        foreach (

            Security::where(
                'status_id',
                Status::ACTIVE
            )->get() as $security

        ) {

            SecurityPriceHistory::create([

                'security_id' => $security->id,

                'price_date' => $today,

                'close_price' => 25.44,

                'volume' => 100000,

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            0
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {
        Security::factory()
            ->count(2)
            ->create();

        SecurityPriceHistory::create([

            'security_id' => Security::first()->id,

            'price_date' => now()->toDateString(),

            'close_price' => 25.44,

            'volume' => 100000,

            'data_source_id' => 1,

            'retrieved_at' => now(),

        ]);

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions([

                    'force' => true,

                    'limit' => 2,

                ]);

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            2
        );

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            1
        );
    }

    public function test_it_returns_success_when_no_securities_exist()
    {
        DB::table('securities')
            ->truncate();

        $results =
            $this->handler
                ->handleRunAiSecurityPriceExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            0
        );
    }
}
