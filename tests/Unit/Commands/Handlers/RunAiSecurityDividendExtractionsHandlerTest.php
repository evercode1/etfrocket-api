<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiSecurityDividendExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiSecurityDividendExtractionsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\SecuritySeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityDividendExtractionsHandlerTest extends TestCase
{
    private RunAiSecurityDividendExtractionsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

            SecuritySeeder::class,

        ]);

        Queue::fake();

        $this->handler =
            app(
                RunAiSecurityDividendExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {
        $securityCount =
            Security::count();

        $results =
            $this->handler
                ->handleRunAiSecurityDividendExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityDividendExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_creates_batch_record()
    {
        $this->handler
            ->handleRunAiSecurityDividendExtractions([

                'limit' => 2,

            ]);

        $this->assertDatabaseHas(

            'security_ingestion_batches',

            [

                'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                'status_id' => Status::PENDING,

                'total_securities' => 2,

            ]

        );
    }

    public function test_it_skips_when_all_active_securities_have_fresh_dividend_data()
    {
        foreach (

            Security::where(
                'status_id',
                Status::ACTIVE
            )->get() as $security

        ) {

            SecurityDividendHistory::create([

                'security_id' => $security->id,

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
                ->handleRunAiSecurityDividendExtractions();

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
