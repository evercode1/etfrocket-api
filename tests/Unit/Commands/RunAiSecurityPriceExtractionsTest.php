<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiSecurityPriceExtractionJob;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use Carbon\Carbon;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityPriceExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(

            Carbon::create(

                2026,

                6,

                3, // Wednesday

                19,

                0,

                0

            )

        );

        DB::table('jobs')
            ->truncate();

        DB::table('failed_jobs')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_price_histories')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::table('jobs')
            ->truncate();

        DB::table('failed_jobs')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_price_histories')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {
        $securityCount =
            Security::count();

        $this->artisan(
            'securities:run-ai-price-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {

        $security = Security::create([
            'symbol' => 'CHPY',

            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()
            ->create([

                'security_id' => $security->id,

            ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(

            'securities:run-ai-price-extraction',

            [

                '--symbol' => 'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiSecurityPriceExtractionJob::class,

            function ($job) use ($security) {

                return
                    $job->securityId ===
                    $security->id;
            }

        );

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {

        Security::factory()
            ->count(10)
            ->create();

        $this->artisan(

            'securities:run-ai-price-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            5
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {

        Security::factory()->count(2)->create();

        SecurityPriceHistory::create([

            'security_id' => Security::first()->id,

            'price_date' => now()->toDateString(),

            'close_price' => 25.55,

            'volume' => 100000,

            'data_source_id' => 1,

            'retrieved_at' => now(),

        ]);

        $this->artisan(

            'securities:run-ai-price-extraction',

            [

                '--force' => true,

                '--limit' => 2,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityPriceExtractionJob::class,
            2
        );
    }

    public function test_it_skips_dispatch_when_all_active_securities_have_fresh_price_data()
    {
        $today =
            now()->toDateString();

        Security::factory()->count(3)->create([

            'status_id' => Status::ACTIVE,

        ]);

        foreach (

            Security::where(
                'status_id',
                Status::ACTIVE
            )->get() as $security

        ) {

            SecurityPriceHistory::create([

                'security_id' => $security->id,

                'price_date' => $today,

                'close_price' => 25.55,

                'volume' => 100000,

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $this->artisan(
            'securities:run-ai-price-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_securities_exist()
    {
        DB::table('securities')
            ->truncate();

        $this->artisan(
            'securities:run-ai-price-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
