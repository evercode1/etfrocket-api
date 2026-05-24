<?php

namespace Tests\Unit\Crons;

use App\Models\CronLog;
use App\Models\Interval;
use App\Models\NotificationStatus;
use App\Models\Status;
use App\Services\Crons\CronService;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CronServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cron_logs')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        $this->seed(
            StatusSeeder::class
        );

        $this->seed(
            IntervalSeeder::class
        );

        $this->seed(
            NotificationStatusSeeder::class
        );
    }

    protected function tearDown(): void
    {
        DB::table('cron_logs')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_runs_handler_and_logs_successful_cron()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $this->assertDatabaseCount(
            'cron_logs',
            1
        );

        $cronLog =
            CronLog::first();

        $this->assertEquals(
            'app:trim-cron-logs',
            $cronLog->cron_name
        );

        $this->assertEquals(
            'remove old cron logs',
            $cronLog->cron_description
        );

        $this->assertNotNull(
            $cronLog->status_id
        );

        $this->assertNotNull(
            $cronLog->interval_id
        );

        $this->assertNotNull(
            $cronLog->notification_status_id
        );
    }

    public function test_it_sets_completed_status_for_successful_cron()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $completedStatusId =
            Status::getStatusId(
                'completed'
            );

        $this->assertEquals(

            $completedStatusId,

            $cronLog->status_id

        );
    }

    public function test_it_sets_interval_id_correctly()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $weeklyIntervalId =
            Interval::getIntervalId(
                'Weekly'
            );

        $this->assertEquals(

            $weeklyIntervalId,

            $cronLog->interval_id

        );
    }

    public function test_it_sets_notification_status_for_successful_cron()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $expectedNotificationStatusId =

            NotificationStatus::getNotificationStatusId(
                'nothing to send'
            );

        $this->assertEquals(

            $expectedNotificationStatusId,

            $cronLog->notification_status_id

        );
    }

    public function test_it_sets_start_and_end_times()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $this->assertNotNull(
            $cronLog->start_time
        );

        $this->assertNotNull(
            $cronLog->end_time
        );
    }

    public function test_it_sets_run_time()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $this->assertIsInt(
            $cronLog->run_time
        );

        $this->assertGreaterThanOrEqual(
            0,
            $cronLog->run_time
        );
    }

    public function test_it_saves_null_failure_details_for_successful_cron()
    {
        CronService::runAndLogCron(

            'app:trim-cron-logs',

            'remove old cron logs',

            'TrimCronLogsHandler',

            'handleTrimCronLogs',

            'Weekly'

        );

        $cronLog =
            CronLog::first();

        $this->assertNull(
            $cronLog->cron_fail_details
        );
    }
}
