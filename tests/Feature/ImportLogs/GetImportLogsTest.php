<?php

namespace Tests\Feature\ImportLogs;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetImportLogsTest extends TestCase
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

    public function test_it_returns_import_logs()
    {
        ImportLog::factory()
            ->count(3)
            ->create([

                'import_type_id' => ImportType::SECURITY_PRICE_IMPORT,

                'status_id' => Status::COMPLETED,

                'data_source_id' => DataSource::TIINGO_API,

            ]);

        $response =
            $this->getJson(
                '/api/import-logs'
            );

        $response->assertStatus(200)

            ->assertJson([

                'success' => true,

            ]);

        $this->assertCount(

            3,

            $response->json(
                'logs.data'
            )

        );
    }

    public function test_it_returns_expected_import_log_fields()
    {
        ImportLog::factory()
            ->create([

                'import_type_id' => ImportType::MARKET_SNAPSHOT,

                'status_id' => Status::COMPLETED,

                'data_source_id' => DataSource::AI_SCRAPER,

            ]);

        $response =
            $this->getJson(
                '/api/import-logs'
            );

        $response->assertStatus(200)

            ->assertJsonStructure([

                'success',

                'logs' => [

                    'data' => [

                        '*' => [

                            'id',

                            'run_time',

                            'rows_processed',

                            'records_created',

                            'records_updated',

                            'duplicate_rows',

                            'failure_count',

                            'passed_data_integrity_check',

                            'started_at',

                            'completed_at',

                            'import_type_name',

                            'status_name',

                            'data_source_name',

                        ],

                    ],

                ],

            ]);
    }

    public function test_it_returns_logs_ordered_by_started_at_descending()
    {
        ImportLog::factory()
            ->create([

                'started_at' => now()->subHour(),

            ]);

        ImportLog::factory()
            ->create([

                'started_at' => now(),

            ]);

        $response =
            $this->getJson(
                '/api/import-logs'
            );

        $response->assertStatus(200);

        $logs =
            $response->json(
                'logs.data'
            );

        $this->assertTrue(

            $logs[0]['started_at'] >

                $logs[1]['started_at']

        );
    }

    public function test_it_returns_empty_logs_when_no_import_logs_exist()
    {
        $response =
            $this->getJson(
                '/api/import-logs'
            );

        $response->assertStatus(200)

            ->assertJson([

                'success' => true,

            ]);

        $this->assertCount(

            0,

            $response->json(
                'logs.data'
            )

        );
    }
}
