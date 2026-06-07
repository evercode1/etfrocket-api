<?php

namespace Tests\Unit\Commands;

use App\Jobs\GenerateAiSignalJob;
use App\Models\AiSignalBatch;
use App\Models\CronLog;
use App\Models\SignalType;
use Carbon\Carbon;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\SignalTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateAiSignalCommandTest extends TestCase
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

        Queue::fake();

        DB::table('ai_signal_batch_items')->truncate();

        DB::table('ai_signal_batches')->truncate();

        DB::table('ai_market_signals')->truncate();

        DB::table('cron_logs')->truncate();

        DB::table('signal_types')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

            SignalTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('ai_signal_batch_items')->truncate();

        DB::table('ai_signal_batches')->truncate();

        DB::table('ai_market_signals')->truncate();

        DB::table('cron_logs')->truncate();

        DB::table('signal_types')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_dispatches_all_ai_signal_jobs(): void
    {
        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            GenerateAiSignalJob::class,

            3

        );
    }

    public function test_it_creates_ai_signal_batch(): void
    {
        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'ai_signal_batches',

            1

        );

        $batch =

            AiSignalBatch::first();

        $this->assertNotNull(

            $batch

        );

        $this->assertEquals(

            3,

            $batch->total_signals

        );

        $this->assertNotNull(

            $batch->batch_uuid

        );
    }

    public function test_it_creates_batch_items_for_all_signal_types(): void
    {
        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'ai_signal_batch_items',

            3

        );

        $this->assertDatabaseHas(

            'ai_signal_batch_items',

            [

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

            ]

        );

        $this->assertDatabaseHas(

            'ai_signal_batch_items',

            [

                'signal_type_id' => SignalType::MARKET_CONDITIONS,

            ]

        );

        $this->assertDatabaseHas(

            'ai_signal_batch_items',

            [

                'signal_type_id' => SignalType::ETF_WATCHLIST,

            ]

        );
    }

    public function test_it_creates_cron_log_record(): void
    {
        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'cron_logs',

            1

        );

        $this->assertDatabaseHas(

            'cron_logs',

            [

                'cron_name' => 'ai:generate-signals

        {--force : Force signal generation even if no fresh data exists}',

                'cron_description' => 'Generate AI market signals',

            ]

        );
    }

    public function test_it_creates_successful_cron_log(): void
    {
        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $cronLog =

            CronLog::first();

        $this->assertNotNull(

            $cronLog

        );

        $this->assertNotNull(

            $cronLog->status_id

        );

        $this->assertNotNull(

            $cronLog->run_time

        );

        $this->assertNotNull(

            $cronLog->start_time

        );

        $this->assertNotNull(

            $cronLog->end_time

        );
    }
}
