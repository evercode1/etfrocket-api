<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiEtfAumExtractionJob;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\Status;
use Database\Seeders\EtfSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiEtfAumExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etf_aum_histories')->truncate();

        DB::table('etfs')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

            EtfSeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etf_aum_histories')->truncate();

        DB::table('etfs')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_etfs()
    {
        $etfCount =
            Etf::count();

        $this->artisan(
            'etfs:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiEtfAumExtractionJob::class,
            $etfCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(

            'etfs:run-ai-aum-extraction',

            [

                '--symbol' => 'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiEtfAumExtractionJob::class,

            function ($job) use ($etf) {

                return
                    $job->etfId ===
                    $etf->id;
            }

        );

        Queue::assertPushed(
            RunAiEtfAumExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {
        $this->artisan(

            'etfs:run-ai-aum-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiEtfAumExtractionJob::class,
            5
        );
    }

    public function test_it_skips_dispatch_when_all_active_etfs_have_fresh_aum_data()
    {
        foreach (

            Etf::where(
                'status_id',
                Status::ACTIVE
            )->get() as $etf

        ) {

            EtfAumHistory::create([

                'etf_id' => $etf->id,

                'assets_under_management' => 100000000,

                'aum_date' => now()->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $this->artisan(
            'etfs:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_skips_dispatch_when_aum_data_is_fresh()
    {
        foreach (

            Etf::where(
                'status_id',
                Status::ACTIVE
            )->get() as $etf

        ) {

            EtfAumHistory::create([

                'etf_id' => $etf->id,

                'assets_under_management' => 100000000,

                'aum_date' => now()->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $this->artisan(
            'etfs:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_etfs_exist()
    {
        DB::table('etfs')
            ->truncate();

        $this->artisan(
            'etfs:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
