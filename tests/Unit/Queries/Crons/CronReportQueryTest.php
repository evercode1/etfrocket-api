<?php

namespace Tests\Unit\Queries\Crons;

use App\Models\CronLog;
use App\Models\Interval;
use App\Models\NotificationStatus;
use App\Models\Status;
use App\Queries\Admin\Monitoring\CronReportQuery;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CronReportQueryTest extends TestCase
{
    private CronReportQuery $query;

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

        $this->query =
            new CronReportQuery;
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

    public function test_it_returns_summary_data()
    {
        CronLog::factory()
            ->count(3)
            ->create([

                'status_id' => Status::getStatusId(
                    'completed'
                ),

            ]);

        CronLog::factory()
            ->count(2)
            ->create([

                'status_id' => Status::getStatusId(
                    'failed'
                ),

            ]);

        $results =
            $this->query
                ->getData();

        $this->assertArrayHasKey(
            'summary',
            $results
        );

        $this->assertEquals(

            3,

            $results['summary']['successful_runs']

        );

        $this->assertEquals(

            2,

            $results['summary']['failed_runs']

        );
    }

    public function test_it_returns_average_runtime()
    {
        CronLog::factory()
            ->create([

                'run_time' => 5,

            ]);

        CronLog::factory()
            ->create([

                'run_time' => 15,

            ]);

        $results =
            $this->query
                ->getData();

        $this->assertEquals(

            10,

            $results['summary']['average_runtime']

        );
    }

    public function test_it_returns_active_cron_count()
    {
        CronLog::factory()
            ->create([

                'cron_name' => 'cron-a',

            ]);

        CronLog::factory()
            ->create([

                'cron_name' => 'cron-a',

            ]);

        CronLog::factory()
            ->create([

                'cron_name' => 'cron-b',

            ]);

        $results =
            $this->query
                ->getData();

        $this->assertEquals(

            2,

            $results['summary']['active_crons']

        );
    }

    public function test_it_returns_paginated_logs()
    {
        CronLog::factory()
            ->count(5)
            ->create();

        $results =
            $this->query
                ->getData();

        $this->assertArrayHasKey(
            'logs',
            $results
        );

        $this->assertEquals(

            5,

            $results['logs']->count()

        );
    }

    public function test_it_returns_logs_with_relationship_data()
    {
        CronLog::factory()
            ->create([

                'status_id' => Status::getStatusId(
                    'completed'
                ),

                'interval_id' => Interval::getIntervalId(
                    'Daily'
                ),

                'notification_status_id' => NotificationStatus::getNotificationStatusId(
                    'nothing to send'
                ),

            ]);

        $results =
            $this->query
                ->getData();

        $log =
            $results['logs']
                ->first();

        $this->assertNotNull(
            $log->status_name
        );

        $this->assertNotNull(
            $log->interval_name
        );

        $this->assertNotNull(
            $log->notification_status_name
        );
    }

    public function test_it_orders_logs_by_start_time_descending()
    {
        CronLog::factory()
            ->create([

                'cron_name' => 'older-cron',

                'start_time' => now()->subHour(),

            ]);

        CronLog::factory()
            ->create([

                'cron_name' => 'newer-cron',

                'start_time' => now(),

            ]);

        $results =
            $this->query
                ->getData();

        $logs =
            $results['logs'];

        $this->assertEquals(

            'newer-cron',

            $logs[0]->cron_name

        );

        $this->assertEquals(

            'older-cron',

            $logs[1]->cron_name

        );
    }

    public function test_it_returns_zero_summary_values_when_no_logs_exist()
    {
        $results =
            $this->query
                ->getData();

        $this->assertEquals(

            0,

            $results['summary']['successful_runs']

        );

        $this->assertEquals(

            0,

            $results['summary']['failed_runs']

        );

        $this->assertEquals(

            0,

            $results['summary']['average_runtime']

        );

        $this->assertEquals(

            0,

            $results['summary']['active_crons']

        );

        $this->assertEquals(

            0,

            $results['logs']->count()

        );
    }
}
