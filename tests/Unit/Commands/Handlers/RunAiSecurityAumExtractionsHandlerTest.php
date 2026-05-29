<?php

namespace Tests\Unit\Commands\Handlers;

use App\Jobs\RunAiSecurityAumExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\Status;
use App\Services\Crons\Handlers\RunAiSecurityAumExtractionsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityAumExtractionsHandlerTest extends TestCase
{
    private RunAiSecurityAumExtractionsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_aum_histories')->truncate();

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
                RunAiSecurityAumExtractionsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')->truncate();

        DB::table('security_ingestion_batches')->truncate();

        DB::table('security_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('import_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {

        Security::create([

            'name' => 'CHPY',

            'status_id' => Status::ACTIVE,
        ]);

        $securityCount =
            Security::count();

        $results =
            $this->handler
                ->handleRunAiSecurityAumExtractions();

        $this->assertEquals(
            1,
            $results['success']
        );

        Queue::assertPushed(
            RunAiSecurityAumExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_creates_batch_record()
    {

        Security::create([

            'symbol' => 'NVII',

            'status_id' => Status::ACTIVE,
        ]);

        Security::create([

            'symbol' => 'GOOY',

            'status_id' => Status::ACTIVE,
        ]);
        $this->handler
            ->handleRunAiSecurityAumExtractions([

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

    public function test_it_skips_when_all_active_securities_have_fresh_aum_data()
    {

        Security::factory()
            ->count(3)
            ->create([

                'status_id' => Status::ACTIVE,

            ]);
        foreach (

            Security::where(
                'status_id',
                Status::ACTIVE
            )->get() as $security

        ) {

            SecurityAumHistory::create([

                'security_id' => $security->id,

                'assets_under_management' => 100000000,

                'aum_date' => now()->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $results =
            $this->handler
                ->handleRunAiSecurityAumExtractions();

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
