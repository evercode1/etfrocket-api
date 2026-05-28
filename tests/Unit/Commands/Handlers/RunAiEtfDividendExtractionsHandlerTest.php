<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiEtfDividendExtractionJob;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\ImportType;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiEtfDividendExtractionsHandler;
use Database\Seeders\EtfSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiEtfDividendExtractionsHandlerTest extends TestCase
{
    private RunAiEtfDividendExtractionsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_ingestion_batch_items')->truncate();

        DB::table('etf_ingestion_batches')->truncate();

        DB::table('etf_dividend_histories')->truncate();

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
                RunAiEtfDividendExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_ingestion_batch_items')->truncate();

        DB::table('etf_ingestion_batches')->truncate();

        DB::table('etf_dividend_histories')->truncate();

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
                ->handleRunAiEtfDividendExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiEtfDividendExtractionJob::class,
            $etfCount
        );
    }

    public function test_it_creates_batch_record()
    {
        $this->handler
            ->handleRunAiEtfDividendExtractions([

                'limit' => 2,

            ]);

        $this->assertDatabaseHas(

            'etf_ingestion_batches',

            [

                'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                'status_id' => Status::PENDING,

                'total_etfs' => 2,

            ]

        );
    }

    public function test_it_skips_when_all_active_etfs_have_fresh_dividend_data()
    {
        foreach (

            Etf::where(
                'status_id',
                Status::ACTIVE
            )->get() as $etf

        ) {

            EtfDividendHistory::create([

                'etf_id' => $etf->id,

                'dividend_amount' => 0.25,

                'ex_dividend_date' => now()->toDateString(),

                'payment_date' => now()
                    ->addDays(5)
                    ->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $results =
            $this->handler
                ->handleRunAiEtfDividendExtractions();

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
