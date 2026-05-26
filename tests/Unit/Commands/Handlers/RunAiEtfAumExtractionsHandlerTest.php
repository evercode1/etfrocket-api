<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiEtfAumExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfIngestionBatch;
use App\Models\ImportType;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiEtfAumExtractionsHandler;
use Database\Seeders\EtfSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiEtfAumExtractionsHandlerTest extends TestCase
{
    private RunAiEtfAumExtractionsHandler
        $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_ingestion_batch_items')->truncate();

        DB::table('etf_ingestion_batches')->truncate();

        DB::table('etf_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

            EtfSeeder::class,

        ]);

        Queue::fake();

        $this->handler =
            app(
                RunAiEtfAumExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_ingestion_batch_items')->truncate();

        DB::table('etf_ingestion_batches')->truncate();

        DB::table('etf_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_etfs()
    {
        $etfCount =
            Etf::count();

        $results =
            $this->handler
            ->handleRunAiEtfAumExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiEtfAumExtractionJob::class,
            $etfCount
        );
    }

    public function test_it_creates_batch_record()
    {
        $this->handler
            ->handleRunAiEtfAumExtractions([

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

            ]

        );
    }

    public function test_it_skips_when_aum_data_is_fresh()
    {
        EtfAumHistory::create([

            'etf_id' =>
            Etf::first()->id,

            'assets_under_management' =>
            100000000,

            'aum_date' =>
            now()->toDateString(),

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
            ->handleRunAiEtfAumExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertNothingPushed();
    }
}
