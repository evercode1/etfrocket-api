<?php

namespace Tests\Unit\ImportLogs;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Status;
use App\Services\ImportLogs\ImportLogsService;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLogsServiceTest extends TestCase
{
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

    public function test_it_creates_import_log()
    {
        $log =
            ImportLogsService::log(

                import_type_id: ImportType::SECURITY_PRICE_IMPORT,

                status_id: Status::COMPLETED,

                data_source_id: DataSource::TIINGO_API,

                run_time: 12,

                rows_processed: 1000,

                records_created: 750,

                records_updated: 250,

                duplicate_rows: 5,

                failure_count: 0,

                passed_data_integrity_check: true,

                generated_markdown: '# Import Complete',

                processing_notes: 'Import completed successfully.',

                import_fail_details: null,

                started_at: now()->subSeconds(12),

                completed_at: now(),

            );

        $this->assertInstanceOf(
            ImportLog::class,
            $log
        );

        $this->assertDatabaseHas(
            'import_logs',
            [

                'id' => $log->id,

                'import_type_id' => ImportType::SECURITY_PRICE_IMPORT,

                'status_id' => Status::COMPLETED,

                'data_source_id' => DataSource::TIINGO_API,

                'run_time' => 12,

                'rows_processed' => 1000,

                'records_created' => 750,

                'records_updated' => 250,

                'duplicate_rows' => 5,

                'failure_count' => 0,

                'passed_data_integrity_check' => 1,

            ]
        );
    }

    public function test_it_can_store_generated_markdown()
    {
        $log =
            ImportLogsService::log(

                import_type_id: ImportType::MARKET_SNAPSHOT,

                status_id: Status::COMPLETED,

                generated_markdown: '# AI Snapshot'

            );

        $this->assertEquals(

            '# AI Snapshot',

            $log->generated_markdown

        );
    }

    public function test_it_can_store_failure_details()
    {
        $log =
            ImportLogsService::log(

                import_type_id: ImportType::SECURITY_NAV_IMPORT,

                status_id: Status::FAILED,

                import_fail_details: 'Provider timeout occurred.',

                failure_count: 1

            );

        $this->assertEquals(

            'Provider timeout occurred.',

            $log->import_fail_details

        );

        $this->assertEquals(

            1,

            $log->failure_count

        );
    }

    public function test_it_sets_default_timestamps_when_not_provided()
    {
        $log =
            ImportLogsService::log(

                import_type_id: ImportType::SECURITY_AUM_IMPORT,

                status_id: Status::COMPLETED

            );

        $this->assertNotNull(
            $log->started_at
        );

        $this->assertNotNull(
            $log->completed_at
        );
    }

    public function test_it_defaults_integrity_check_to_false()
    {
        $log =
            ImportLogsService::log(

                import_type_id: ImportType::DATA_INTEGRITY_AUDIT,

                status_id: Status::FAILED

            );

        $this->assertEquals(

            0,

            $log->passed_data_integrity_check

        );
    }
}
