<?php

namespace Tests\Unit\Commands;

use App\Models\Security;
use App\Models\SecurityPriceHistory;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportSecurityBackfillHistoryTest extends TestCase
{
    private string $testImportPath;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

        ]);

        $this->testImportPath =
            storage_path(
                'framework/testing/imports'
            );

        if (! file_exists($this->testImportPath)) {

            mkdir(

                $this->testImportPath,

                0777,

                true

            );
        }

        config([

            'imports.path' => $this->testImportPath,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        DB::table('statuses')->truncate();

        if (

            file_exists(

                $this->testImportPath.
                    '/security_price_histories.csv'

            )

        ) {

            unlink(

                $this->testImportPath.
                    '/security_price_histories.csv'

            );
        }

        parent::tearDown();
    }

    public function test_it_imports_price_history_rows()
    {

        Security::factory()->create([
            'symbol' => 'TEST',
        ]);

        $security = Security::firstOrFail();

        $csv =

            "security_id,price_date,close_price,volume,data_source_id,retrieved_at\n".

            "{$security->id},2026-05-26,25.44,100000,1,2026-05-26 12:00:00\n".

            "{$security->id},2026-05-27,26.11,150000,1,2026-05-27 12:00:00\n";

        file_put_contents(

            $this->testImportPath.
                '/security_price_histories.csv',

            $csv

        );

        $this->artisan(

            'securities:import-backfill-history',

            [

                'table' => 'security_price_histories',

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'security_price_histories',
            2
        );

        $this->assertDatabaseHas(

            'security_price_histories',

            [

                'security_id' => $security->id,

                'price_date' => '2026-05-26',

                'close_price' => 25.44,

            ]

        );

        $this->assertDatabaseHas(

            'security_price_histories',

            [

                'security_id' => $security->id,

                'price_date' => '2026-05-27',

                'close_price' => 26.11,

            ]

        );
    }

    public function test_it_truncates_existing_records()
    {

        Security::factory()->create([
            'symbol' => 'TEST',
        ]);

        $security = Security::firstOrFail();

        SecurityPriceHistory::create([

            'security_id' => $security->id,

            'price_date' => '2025-01-01',

            'close_price' => 10.00,

            'volume' => 1000,

            'data_source_id' => 1,

            'retrieved_at' => now(),

        ]);

        $csv =

            "security_id,price_date,close_price,volume,data_source_id,retrieved_at\n".

            "{$security->id},2026-05-26,25.44,100000,1,2026-05-26 12:00:00\n";

        file_put_contents(

            $this->testImportPath.
                '/security_price_histories.csv',

            $csv

        );

        $this->artisan(

            'securities:import-backfill-history',

            [

                'table' => 'security_price_histories',

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'security_price_histories',
            1
        );

        $this->assertDatabaseMissing(

            'security_price_histories',

            [

                'price_date' => '2025-01-01',

            ]

        );
    }

    public function test_it_rejects_invalid_table()
    {
        $this->artisan(

            'securities:import-backfill-history',

            [

                'table' => 'invalid_table',

            ]

        )

            ->expectsOutput(
                'Invalid import table.'
            )

            ->assertExitCode(1);
    }

    public function test_it_processes_chunked_imports()
    {

        Security::factory()->create([
            'symbol' => 'TEST',
        ]);

        $security = Security::firstOrFail();

        $csv =

            "security_id,price_date,close_price,volume,data_source_id,retrieved_at\n";

        for ($i = 1; $i <= 5; $i++) {

            $csv .=

                "{$security->id},2026-05-0{$i},25.{$i},100{$i},1,2026-05-0{$i} 12:00:00\n";
        }

        file_put_contents(

            $this->testImportPath.
                '/security_price_histories.csv',

            $csv

        );

        $this->artisan(

            'securities:import-backfill-history',

            [

                'table' => 'security_price_histories',

                '--chunk' => 2,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(
            'security_price_histories',
            5
        );
    }
}
