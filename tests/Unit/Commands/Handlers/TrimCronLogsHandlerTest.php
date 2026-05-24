<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\CronLog;
use App\Services\Crons\Handlers\TrimCronLogsHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrimCronLogsHandlerTest extends TestCase
{
    private TrimCronLogsHandler
        $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cron_logs')
            ->truncate();

        $this->handler =
            new TrimCronLogsHandler();
    }

    protected function tearDown(): void
    {
        DB::table('cron_logs')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_deletes_logs_older_than_one_week()
    {
        CronLog::factory()
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(10),

            ]);

        CronLog::factory()
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(2),

            ]);

        $results =
            $this->handler
            ->handleTrimCronLogs();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertDatabaseCount(
            'cron_logs',
            1
        );
    }

    public function test_it_preserves_recent_logs()
    {
        CronLog::factory()
            ->count(3)
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(2),

            ]);

        $results =
            $this->handler
            ->handleTrimCronLogs();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertDatabaseCount(
            'cron_logs',
            3
        );
    }

    public function test_it_deletes_multiple_old_logs()
    {
        CronLog::factory()
            ->count(5)
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(14),

            ]);

        $results =
            $this->handler
            ->handleTrimCronLogs();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertDatabaseCount(
            'cron_logs',
            0
        );
    }

    public function test_it_returns_success_when_no_logs_exist()
    {
        $results =
            $this->handler
            ->handleTrimCronLogs();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertNull(
            $results['cron_fail_details']
        );
    }

    public function test_it_returns_success_when_no_old_logs_exist()
    {
        CronLog::factory()
            ->count(2)
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(3),

            ]);

        $results =
            $this->handler
            ->handleTrimCronLogs();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertNull(
            $results['cron_fail_details']
        );

        $this->assertDatabaseCount(
            'cron_logs',
            2
        );
    }

    public function test_it_only_deletes_logs_older_than_seven_days()
    {
        CronLog::factory()
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(8),

            ]);

        CronLog::factory()
            ->create([

                'created_at' =>
                Carbon::now()
                    ->subDays(6),

            ]);

        $this->handler
            ->handleTrimCronLogs();

        $this->assertDatabaseCount(
            'cron_logs',
            1
        );
    }
}
