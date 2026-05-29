<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiSecurityNavExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiSecurityNavExtractionsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityNavExtractionsHandlerTest extends TestCase
{
    private RunAiSecurityNavExtractionsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

            ImportTypeSeeder::class,

        ]);

        Queue::fake();

        $this->handler =
            app(
                RunAiSecurityNavExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {

        Security::factory()->create();

        $securityCount =
            Security::count();

        $results =
            $this->handler
                ->handleRunAiSecurityNavExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityNavExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_creates_batch_record()
    {

        Security::factory()->count(2)->create();

        $this->handler
            ->handleRunAiSecurityNavExtractions([

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

    public function test_it_skips_when_all_active_securities_have_fresh_nav_data()
    {

        Security::factory()->count(3)->create();

        foreach (

            Security::where(
                'status_id',
                Status::ACTIVE
            )->get() as $security

        ) {

            SecurityNavHistory::create([

                'security_id' => $security->id,

                'nav_per_share' => 25.44,

                'nav_date' => now()->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $results =
            $this->handler
                ->handleRunAiSecurityNavExtractions();

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
