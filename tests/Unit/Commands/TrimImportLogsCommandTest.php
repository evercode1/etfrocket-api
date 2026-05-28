<?php

namespace Tests\Unit\Commands;

use App\Models\CronLog;
use App\Models\ImportLog;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrimImportLogsCommandTest extends TestCase
{
    protected function setUp(): void
    {

        parent::setUp();

        DB::table('cron_logs')

            ->truncate();

        DB::table('import_logs')

            ->truncate();

        DB::table('import_types')

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

            ImportTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {

        DB::table('cron_logs')

            ->truncate();

        DB::table('import_logs')

            ->truncate();

        DB::table('import_types')

            ->truncate();

        DB::table('intervals')

            ->truncate();

        DB::table('statuses')

            ->truncate();

        DB::table('notification_statuses')

            ->truncate();

        parent::tearDown();
    }

    public function test_it_runs_trim_import_logs_command()
    {

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(8),

            ]);

        $this->artisan(

            'app:trim-import-logs'

        )->assertExitCode(0);

        $this->assertEquals(

            0,

            ImportLog::count()

        );
    }

    public function test_it_creates_cron_log_record()
    {

        $this->artisan(

            'app:trim-import-logs'

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'cron_logs',

            1

        );

        $this->assertDatabaseHas(

            'cron_logs',

            [

                'cron_name' => 'app:trim-import-logs',

                'cron_description' => 'remove old import logs',

            ]

        );
    }

    public function test_it_creates_successful_cron_log()
    {

        $this->artisan(

            'app:trim-import-logs'

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

    public function test_it_does_not_delete_recent_import_logs()
    {

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(2),

            ]);

        $this->artisan(

            'app:trim-import-logs'

        )->assertExitCode(0);

        $this->assertEquals(

            1,

            ImportLog::count()

        );
    }

    public function test_it_deletes_only_logs_older_than_one_week()
    {

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(8),

            ]);

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(3),

            ]);

        $this->artisan(

            'app:trim-import-logs'

        )->assertExitCode(0);

        $this->assertEquals(

            1,

            ImportLog::count()

        );
    }
}
