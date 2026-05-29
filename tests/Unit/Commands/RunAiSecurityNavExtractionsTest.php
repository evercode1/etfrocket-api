<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiSecurityNavExtractionJob;
use App\Models\Security;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityNavExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('security_nav_histories')->truncate();

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

        DB::table('security_nav_histories')->truncate();

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
            'securities:run-ai-nav-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityNavExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {
        Security::factory()
            ->create([
                'symbol' => 'CHPY',
            ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(

            'securities:run-ai-nav-extraction',

            [

                '--symbol' => 'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiSecurityNavExtractionJob::class,

            function ($job) use ($security) {

                return
                    $job->securityId ===
                    $security->id;
            }

        );

        Queue::assertPushed(
            RunAiSecurityNavExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {

        Security::factory()
            ->count(10)
            ->create();

        $this->artisan(

            'securities:run-ai-nav-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityNavExtractionJob::class,
            5
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {

        Security::factory()->count(5)
            ->create();

        SecurityNavHistory::create([

            'security_id' => Security::first()->id,

            'nav_per_share' => 25.44,

            'nav_date' => now()->toDateString(),

            'data_source_id' => 1,

            'retrieved_at' => now(),

        ]);

        $this->artisan(

            'securities:run-ai-nav-extraction',

            [

                '--force' => true,

                '--limit' => 2,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityNavExtractionJob::class,
            2
        );
    }

    public function test_it_skips_dispatch_when_all_active_securities_have_fresh_nav_data()
    {

        Security::factory()
            ->count(5)
            ->create();

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

        $this->artisan(
            'securities:run-ai-nav-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_securities_exist()
    {
        DB::table('securities')
            ->truncate();

        $this->artisan(
            'securities:run-ai-nav-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
