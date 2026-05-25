<?php

namespace Tests\Feature\ImportLogs;

use App\Models\User;

use App\Models\DataSource;

use App\Models\ImportLog;

use App\Models\ImportType;

use App\Models\Status;

use Database\Seeders\DataSourceSeeder;

use Database\Seeders\ImportTypeSeeder;

use Database\Seeders\StatusSeeder;

use Illuminate\Support\Facades\DB;

use Laravel\Sanctum\Sanctum;

use Tests\TestCase;

class ShowImportLogTest extends TestCase

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

        DB::table('users')

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

        $admin =

            User::factory()

            ->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

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

        DB::table('users')

            ->truncate();

        parent::tearDown();
    }

    public function test_it_returns_single_import_log()

    {

        $importLog =

            ImportLog::factory()

            ->create([

                'import_type_id' =>

                ImportType::ETF_PRICE_IMPORT,

                'status_id' =>

                Status::COMPLETED,

                'data_source_id' =>

                DataSource::TIINGO_API,

            ]);

        $response =

            $this->getJson(

                '/api/import-log/' . $importLog->id

            );

        $response->assertStatus(200)

            ->assertJson([

                'success' => true,

                'log' => [

                    'id' =>

                    $importLog->id,

                ],

            ]);
    }

    public function test_it_returns_expected_import_log_fields()

    {

        $importLog =

            ImportLog::factory()

            ->create([

                'generated_markdown' =>

                '# AI Market Snapshot',

                'processing_notes' =>

                'Import completed successfully.',

                'import_fail_details' =>

                null,

            ]);

        $response =

            $this->getJson(

                '/api/import-log/' . $importLog->id

            );

        $response->assertStatus(200)

            ->assertJsonStructure([

                'success',

                'log' => [

                    'id',

                    'run_time',

                    'rows_processed',

                    'records_created',

                    'records_updated',

                    'duplicate_rows',

                    'failure_count',

                    'generated_markdown',

                    'processing_notes',

                    'import_fail_details',

                    'passed_data_integrity_check',

                    'started_at',

                    'completed_at',

                    'import_type_name',

                    'status_name',

                    'data_source_name',

                ],

            ]);
    }

    public function test_it_returns_generated_markdown()

    {

        $importLog =

            ImportLog::factory()

            ->create([

                'generated_markdown' =>

                '# AI Market Snapshot',

            ]);

        $response =

            $this->getJson(

                '/api/import-log/' . $importLog->id

            );

        $response->assertStatus(200)

            ->assertJson([

                'log' => [

                    'generated_markdown' =>

                    '# AI Market Snapshot',

                ],

            ]);
    }

    public function test_it_returns_processing_notes()

    {

        $importLog =

            ImportLog::factory()

            ->create([

                'processing_notes' =>

                'Import completed successfully.',

            ]);

        $response =

            $this->getJson(

                '/api/import-log/' . $importLog->id

            );

        $response->assertStatus(200)

            ->assertJson([

                'log' => [

                    'processing_notes' =>

                    'Import completed successfully.',

                ],

            ]);
    }

    public function test_it_returns_import_fail_details()

    {

        $importLog =

            ImportLog::factory()

            ->create([

                'import_fail_details' =>

                'Provider timeout occurred.',

            ]);

        $response =

            $this->getJson(

                '/api/import-log/' . $importLog->id

            );

        $response->assertStatus(200)

            ->assertJson([

                'log' => [

                    'import_fail_details' =>

                    'Provider timeout occurred.',

                ],

            ]);
    }

    public function test_it_returns_not_found_when_import_log_does_not_exist()

    {

        $response =

            $this->getJson(

                '/api/import-log/999999'

            );

        $response->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' =>

                'Import log not found.',

            ]);
    }
}
