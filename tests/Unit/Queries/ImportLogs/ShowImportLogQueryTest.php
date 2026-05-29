<?php

namespace Tests\Unit\Queries\ImportLogs;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Status;
use App\Queries\ImportLogs\ShowImportLogQuery;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowImportLogQueryTest extends TestCase
{
    private ShowImportLogQuery $query;

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
            new ShowImportLogQuery;
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

    public function test_it_returns_single_import_log()
    {
        $importLog =
            ImportLog::factory()
                ->create();

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertEquals(

            $importLog->id,

            $result->id

        );
    }

    public function test_it_returns_relationship_fields()
    {
        $importLog =
            ImportLog::factory()
                ->create([

                    'import_type_id' => ImportType::SECURITY_PRICE_IMPORT,

                    'status_id' => Status::COMPLETED,

                    'data_source_id' => DataSource::TIINGO_API,

                ]);

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertNotNull(
            $result->import_type_name
        );

        $this->assertNotNull(
            $result->status_name
        );

        $this->assertNotNull(
            $result->data_source_name
        );
    }

    public function test_it_returns_generated_markdown()
    {
        $importLog =
            ImportLog::factory()
                ->create([

                    'generated_markdown' => '# Generated AI Content',

                ]);

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertEquals(

            '# Generated AI Content',

            $result->generated_markdown

        );
    }

    public function test_it_returns_processing_notes()
    {
        $importLog =
            ImportLog::factory()
                ->create([

                    'processing_notes' => 'Import completed successfully.',

                ]);

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertEquals(

            'Import completed successfully.',

            $result->processing_notes

        );
    }

    public function test_it_returns_failure_details()
    {
        $importLog =
            ImportLog::factory()
                ->create([

                    'import_fail_details' => 'Provider timeout occurred.',

                ]);

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertEquals(

            'Provider timeout occurred.',

            $result->import_fail_details

        );
    }

    public function test_it_returns_integrity_check_status()
    {
        $importLog =
            ImportLog::factory()
                ->create([

                    'passed_data_integrity_check' => 1,

                ]);

        $result =
            $this->query
                ->getData(
                    $importLog->id
                );

        $this->assertEquals(

            1,

            $result->passed_data_integrity_check

        );
    }

    public function test_it_throws_exception_when_import_log_not_found()
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        $this->query
            ->getData(999999);
    }
}
