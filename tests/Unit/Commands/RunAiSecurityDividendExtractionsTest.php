<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiSecurityDividendExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use Carbon\Carbon;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\SecuritySeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiSecurityDividendExtractionsTest extends TestCase
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

        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('security_dividend_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

            SecuritySeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('security_dividend_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_securities()
    {
        $securityCount =
            Security::count();

        $this->artisan(
            'securities:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityDividendExtractionJob::class,
            $securityCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {
        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(

            'securities:run-ai-dividend-extraction',

            [

                '--symbol' => 'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiSecurityDividendExtractionJob::class,

            function ($job) use ($security) {

                return
                    $job->securityId ===
                    $security->id;
            }

        );

        Queue::assertPushed(
            RunAiSecurityDividendExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {
        $this->artisan(

            'securities:run-ai-dividend-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityDividendExtractionJob::class,
            5
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {
        SecurityDividendHistory::create([

            'security_id' => Security::first()->id,

            'dividend_amount' => 0.25,

            'ex_dividend_date' => now()->toDateString(),

            'payment_date' => now()->addDays(7)->toDateString(),

            'data_source_id' => 1,

            'retrieved_at' => now(),

        ]);

        AiDataExtraction::factory()
            ->create([

                'created_at' => now(),

            ]);

        $this->artisan(

            'securities:run-ai-dividend-extraction',

            [

                '--force' => true,

                '--limit' => 2,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiSecurityDividendExtractionJob::class,
            2
        );
    }

    public function test_it_skips_dispatch_when_all_active_securities_have_fresh_dividend_data()
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
                    ->addDays(7)
                    ->toDateString(),

                'data_source_id' => 1,

                'retrieved_at' => now(),

            ]);
        }

        $this->artisan(
            'securities:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_securities_exist()
    {
        DB::table('securities')
            ->truncate();

        $this->artisan(
            'securities:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
