<?php

namespace Tests\Unit\Commands;

use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupAiPipelineDataCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cron_logs')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        $this->seed(StatusSeeder::class);

        $this->seed(IntervalSeeder::class);

        $this->seed(NotificationStatusSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('cron_logs')
            ->truncate();

        DB::table('notification_statuses')
            ->truncate();

        DB::table('intervals')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        parent::tearDown();
    }

    public function test_command_executes_successfully()
    {
        $this->artisan(
            'app:cleanup-ai-pipeline-data'
        )->assertExitCode(0);
    }
}
