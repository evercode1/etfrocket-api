<?php

namespace Tests\Feature\Crons;

use App\Models\CronLog;
use App\Models\Interval;
use App\Models\NotificationStatus;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CronReportTest extends TestCase
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

        DB::table('users')
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

        $adminUser =
            User::factory()
                ->create([
                    'is_admin' => true,
                ]);

        Sanctum::actingAs(

            $adminUser,

            ['*']

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

        DB::table('users')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_returns_cron_report_data()
    {
        CronLog::factory()
            ->count(3)
            ->create([

                'status_id' => Status::getStatusId(
                    'completed'
                ),

                'interval_id' => Interval::getIntervalId(
                    'Hourly'
                ),

                'notification_status_id' => NotificationStatus::getNotificationStatusId(
                    'nothing to send'
                ),

            ]);

        $response =
            $this->getJson(
                '/api/admin/cron-reports'
            );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonStructure([

            'success',

            'data' => [

                'summary' => [

                    'successful_runs',

                    'failed_runs',

                    'average_runtime',

                    'active_crons',

                ],

                'logs' => [

                    'current_page',

                    'data',

                    'total',

                ],

            ],

        ]);
    }

    public function test_it_returns_summary_metrics()
    {
        CronLog::factory()
            ->count(2)
            ->create([

                'status_id' => Status::getStatusId(
                    'completed'
                ),

            ]);

        CronLog::factory()
            ->create([

                'status_id' => Status::getStatusId(
                    'failed'
                ),

            ]);

        $response =
            $this->getJson(
                '/api/admin/cron-reports'
            );

        $response->assertStatus(200);

        $response->assertJson([

            'data' => [

                'summary' => [

                    'successful_runs' => 2,

                    'failed_runs' => 1,

                ],

            ],

        ]);
    }

    public function test_it_returns_paginated_logs()
    {
        CronLog::factory()
            ->count(5)
            ->create();

        $response =
            $this->getJson(
                '/api/admin/cron-reports'
            );

        $response->assertStatus(200);

        $this->assertCount(

            5,

            $response->json(
                'data.logs.data'
            )

        );
    }

    public function test_it_returns_log_relationship_fields()
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

        $response =
            $this->getJson(
                '/api/admin/cron-reports'
            );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'logs' => [

                    'data' => [

                        '*' => [

                            'id',

                            'cron_name',

                            'cron_description',

                            'cron_fail_details',

                            'run_time',

                            'start_time',

                            'end_time',

                            'status_name',

                            'interval_name',

                            'notification_status_name',

                        ],

                    ],

                ],

            ],

        ]);
    }

    public function test_it_returns_empty_logs_when_no_records_exist()
    {
        $response =
            $this->getJson(
                '/api/admin/cron-reports'
            );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

            'data' => [

                'summary' => [

                    'successful_runs' => 0,

                    'failed_runs' => 0,

                    'average_runtime' => 0,

                    'active_crons' => 0,

                ],

            ],

        ]);

        $this->assertCount(

            0,

            $response->json(
                'data.logs.data'
            )

        );
    }
}
