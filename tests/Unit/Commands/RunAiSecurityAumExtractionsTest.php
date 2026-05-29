<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiSecurityAumExtractionJob;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\Status;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityAumExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('security_aum_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('security_aum_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {

        Security::factory()
            ->count(10)
            ->create();

        $securityCount =
            Security::count();

        $this->artisan(
            'securities:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityAumExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {

        Security::factory()
            ->create(['symbol' => 'CHPY']);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(
            'securities:run-ai-aum-extraction',

            [

                '--symbol' => 'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiSecurityAumExtractionJob::class,

            function ($job) use ($security) {

                return
                    $job->securityId ===
                    $security->id;
            }

        );

        Queue::assertPushed(
            RunAiSecurityAumExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {

        Security::factory()
            ->count(10)
            ->create();

        $this->artisan(

            'securities:run-ai-aum-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityAumExtractionJob::class,
            5
        );
    }

    public function test_it_skips_dispatch_when_all_active_securities_have_fresh_aum_data()
    {

        Security::factory()
            ->count(10)
            ->create();

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

        $this->artisan(
            'securities:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_skips_dispatch_when_aum_data_is_fresh()
    {
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

        $this->artisan(
            'securities:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_securities_exist()
    {
        DB::table('securities')
            ->truncate();

        $this->artisan(
            'securities:run-ai-aum-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
