<?php

namespace Tests\Unit\Queries\ImportLogs;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Status;
use App\Queries\ImportLogs\ListImportLogsQuery;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListImportLogsQueryTest extends TestCase
{
    private ListImportLogsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('import_logs')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('data_sources')
            ->truncate();

        $this->seed(
            StatusSeeder::class
        );

        $this->seed(
            DataSourceSeeder::class
        );

        $this->seed(
            ImportTypeSeeder::class
        );

        $this->query =
            new ListImportLogsQuery;
    }

    protected function tearDown(): void
    {
        DB::table('import_logs')
            ->truncate();

        DB::table('import_types')
            ->truncate();

        DB::table('statuses')
            ->truncate();

        DB::table('data_sources')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_returns_paginated_import_logs()
    {
        ImportLog::factory()
            ->count(5)
            ->create();

        $results =
            $this->query
                ->getData();

        $this->assertEquals(

            5,

            $results->count()

        );
    }

    public function test_it_returns_import_log_relationship_fields()
    {
        ImportLog::factory()
            ->create([

                'import_type_id' => ImportType::SECURITY_PRICE_IMPORT,

                'status_id' => Status::COMPLETED,

                'data_source_id' => DataSource::TIINGO_API,

            ]);

        $results =
            $this->query
                ->getData();

        $log =
            $results->first();

        $this->assertNotNull(
            $log->import_type_name
        );

        $this->assertNotNull(
            $log->status_name
        );

        $this->assertNotNull(
            $log->data_source_name
        );
    }

    public function test_it_returns_expected_import_log_fields()
    {
        ImportLog::factory()
            ->create();

        $results =
            $this->query
                ->getData();

        $log =
            $results->first();

        $this->assertNotNull(

            $log->id

        );

        $this->assertNotNull(

            $log->run_time

        );

        $this->assertNotNull(

            $log->rows_processed

        );

        $this->assertNotNull(

            $log->records_created

        );

        $this->assertNotNull(

            $log->records_updated

        );

        $this->assertNotNull(

            $log->duplicate_rows

        );

        $this->assertNotNull(

            $log->failure_count

        );

        $this->assertNotNull(

            $log->passed_data_integrity_check

        );

        $this->assertNotNull(

            $log->started_at

        );

        $this->assertNotNull(

            $log->completed_at

        );
    }

    public function test_it_orders_logs_by_started_at_descending()
    {
        ImportLog::factory()
            ->create([

                'started_at' => now()->subHour(),

            ]);

        ImportLog::factory()
            ->create([

                'started_at' => now(),

            ]);

        $results =
            $this->query
                ->getData();

        $this->assertTrue(

            $results[0]->started_at >

                $results[1]->started_at

        );
    }

    public function test_it_returns_empty_paginator_when_no_logs_exist()
    {
        $results =
            $this->query
                ->getData();

        $this->assertEquals(

            0,

            $results->count()

        );
    }
}
