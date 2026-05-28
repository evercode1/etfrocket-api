<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\ImportLog;
use App\Services\Crons\Handlers\TrimImportLogsHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrimImportLogsHandlerTest extends TestCase
{
    private TrimImportLogsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(

            Carbon::parse(

                '2026-05-25 12:00:00'

            )

        );

        DB::table('import_logs')->truncate();

        $this->handler =

            app(

                TrimImportLogsHandler::class

            );
    }

    protected function tearDown(): void
    {

        DB::table('import_logs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_deletes_import_logs_older_than_one_week()
    {

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(8),

            ]);

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(10),

            ]);

        ImportLog::factory()

            ->create([

                'created_at' => now()->subDays(3),

            ]);

        $results =

            $this->handler
                ->handleTrimImportLogs();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            1,

            ImportLog::count()

        );
    }

    public function test_it_does_not_delete_recent_import_logs()
    {

        ImportLog::factory()

            ->count(3)

            ->create([

                'created_at' => now()->subDays(2),

            ]);

        $results =

            $this->handler
                ->handleTrimImportLogs();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            3,

            ImportLog::count()

        );
    }

    public function test_it_returns_success_when_no_logs_exist()
    {

        $results =

            $this->handler
                ->handleTrimImportLogs();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertNull(

            $results['cron_fail_details']

        );

        $this->assertEquals(

            0,

            ImportLog::count()

        );
    }

    public function test_it_only_deletes_logs_older_than_exactly_one_week()
    {

        ImportLog::factory()

            ->create([

                'created_at' => now()->subWeek(),

            ]);

        ImportLog::factory()

            ->create([

                'created_at' => now()->subWeek()->subSecond(),

            ]);

        $results =

            $this->handler
                ->handleTrimImportLogs();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            1,

            ImportLog::count()

        );
    }
}
