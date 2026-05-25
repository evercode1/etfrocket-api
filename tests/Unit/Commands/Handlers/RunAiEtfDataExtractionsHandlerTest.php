<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Etf;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Services\AI\Extractions\AiEtfDataExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfDataExtractionService;
use App\Services\Crons\Handlers\RunAiEtfDataExtractionsHandler;
use Carbon\Carbon;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\EtfSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RunAiEtfDataExtractionsHandlerTest extends TestCase

{

    private RunAiEtfDataExtractionsHandler

        $handler;

    private $aiService;

    private $processService;

    protected function setUp(): void

    {

        parent::setUp();

        Carbon::setTestNow(

            Carbon::parse(

                '2026-05-25 12:00:00'

            )

        );

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('statuses')->truncate();
        DB::table('data_sources')->truncate();
        DB::table('ai_data_extractions')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        $this->seed([

            StatusSeeder::class,

            DataSourceSeeder::class,

            ImportTypeSeeder::class,

            EtfSeeder::class,

        ]);

        $this->aiService =

            Mockery::mock(

                AiEtfDataExtractionService::class

            );

        $this->processService =

            Mockery::mock(

                ProcessAiEtfDataExtractionService::class

            );

        $this->handler =

            new RunAiEtfDataExtractionsHandler(

                $this->aiService,

                $this->processService

            );
    }

    protected function tearDown(): void

    {

        Mockery::close();

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('statuses')->truncate();
        DB::table('data_sources')->truncate();
        DB::table('ai_data_extractions')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_processes_all_etfs()

    {

        $etfs =

            Etf::query()

            ->limit(2)

            ->get();

        foreach ($etfs as $etf) {

            $extraction =

                AiDataExtraction::factory()

                ->make([

                    'etf_id' =>

                    $etf->id,

                ]);

            $this->aiService

                ->shouldReceive('extract')

                ->once()

                ->with(

                    Mockery::on(

                        fn($passedEtf) =>

                        $passedEtf->id ===

                            $etf->id

                    )

                )

                ->andReturn(

                    $extraction

                );

            $this->processService

                ->shouldReceive('process')

                ->once()

                ->with(

                    $extraction

                );
        }

        $results =

            $this->handler

            ->handleRunAiEtfDataExtractions([

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );
    }

    public function test_it_processes_single_symbol()

    {

        $etf =

            Etf::where(

                'symbol',

                'CHPY'

            )->firstOrFail();

        $extraction =

            AiDataExtraction::factory()

            ->make([

                'etf_id' =>

                $etf->id,

            ]);

        $this->aiService

            ->shouldReceive('extract')

            ->once()

            ->with(

                Mockery::on(

                    fn($passedEtf) =>

                    $passedEtf->id ===

                        $etf->id

                )

            )

            ->andReturn(

                $extraction

            );

        $this->processService

            ->shouldReceive('process')

            ->once()

            ->with(

                $extraction

            );

        $results =

            $this->handler

            ->handleRunAiEtfDataExtractions([

                'symbol' => 'CHPY',

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );
    }

    public function test_it_skips_when_no_fresh_price_data_exists()

    {

        AiDataExtraction::factory()

            ->create([

                'created_at' =>

                now(),

            ]);

        DB::table('etf_price_histories')

            ->insert([

                'etf_id' => 1,

                'price_date' =>

                now()->toDateString(),

                'close_price' => 10,

                'volume' => 1000,

                'data_source_id' =>

                DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

        $results =

            $this->handler

            ->handleRunAiEtfDataExtractions();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::AI_DATA_EXTRACTION,

                'processing_notes' =>

                'Skipped AI ETF extraction. No fresh ETF price data detected.',

            ]

        );
    }

    public function test_force_flag_bypasses_freshness_check()

    {

        $etf =

            Etf::firstOrFail();

        AiDataExtraction::factory()

            ->create([

                'created_at' =>

                now(),

            ]);

        DB::table('etf_price_histories')

            ->insert([

                'etf_id' =>

                $etf->id,

                'price_date' =>

                now()->toDateString(),

                'close_price' => 10,

                'volume' => 1000,

                'data_source_id' =>

                DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

        $extraction =

            AiDataExtraction::factory()

            ->make([

                'etf_id' =>

                $etf->id,

            ]);

        $this->aiService

            ->shouldReceive('extract')

            ->once()

            ->andReturn(

                $extraction

            );

        $this->processService

            ->shouldReceive('process')

            ->once();

        $results =

            $this->handler

            ->handleRunAiEtfDataExtractions([

                'force' => true,

                'limit' => 1,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );
    }

    public function test_it_logs_aggregated_metrics()

    {

        $etf =

            Etf::firstOrFail();

        DB::table('etf_price_histories')

            ->insert([

                'etf_id' =>

                $etf->id,

                'price_date' =>

                now()->toDateString(),

                'close_price' => 10,

                'volume' => 1000,

                'data_source_id' =>

                DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

        $extraction =

            AiDataExtraction::factory()

            ->make([

                'etf_id' =>

                $etf->id,

            ]);

        $this->aiService

            ->shouldReceive('extract')

            ->once()

            ->andReturn(

                $extraction

            );

        $this->processService

            ->shouldReceive('process')

            ->once();

        $this->handler

            ->handleRunAiEtfDataExtractions([

                'force' => true,

                'limit' => 1,

            ]);

        $log =

            ImportLog::latest()

            ->first();

        $this->assertNotNull(

            $log

        );

        $this->assertEquals(

            ImportType::AI_DATA_EXTRACTION,

            $log->import_type_id

        );

        $this->assertEquals(

            1,

            $log->rows_processed

        );

        $this->assertEquals(

            1,

            $log->records_created

        );

        $this->assertEquals(

            0,

            $log->failure_count

        );

        $this->assertEquals(

            1,

            $log->passed_data_integrity_check

        );
    }

    public function test_it_continues_processing_when_one_etf_fails()
    {

        $etfs =

            Etf::query()

            ->orderBy(

                'symbol'

            )

            ->limit(2)

            ->get();

        $firstEtf =

            $etfs[0];

        $secondEtf =

            $etfs[1];

        $secondExtraction =

            AiDataExtraction::factory()

            ->make([

                'etf_id' =>

                $secondEtf->id,

            ]);

        $this->aiService

            ->shouldReceive('extract')

            ->once()

            ->with(

                Mockery::on(

                    fn($passedEtf) =>

                    $passedEtf->id ===

                        $firstEtf->id

                )

            )

            ->andThrow(

                new \RuntimeException(

                    'AI failed'

                )

            );

        $this->aiService

            ->shouldReceive('extract')

            ->once()

            ->with(

                Mockery::on(

                    fn($passedEtf) =>

                    $passedEtf->id ===

                        $secondEtf->id

                )

            )

            ->andReturn(

                $secondExtraction

            );

        $this->processService

            ->shouldReceive('process')

            ->once()

            ->with(

                $secondExtraction

            );

        DB::table('etf_price_histories')

            ->insert([

                [

                    'etf_id' =>

                    $firstEtf->id,

                    'price_date' =>

                    now()->toDateString(),

                    'close_price' => 10,

                    'volume' => 1000,

                    'data_source_id' =>

                    DataSource::MANUAL_ENTRY,

                    'retrieved_at' => now(),

                ],

                [

                    'etf_id' =>

                    $secondEtf->id,

                    'price_date' =>

                    now()->toDateString(),

                    'close_price' => 10,

                    'volume' => 1000,

                    'data_source_id' =>

                    DataSource::MANUAL_ENTRY,

                    'retrieved_at' => now(),

                ],

            ]);

        $results =

            $this->handler

            ->handleRunAiEtfDataExtractions([

                'force' => true,

                'limit' => 2,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $log =

            ImportLog::latest()

            ->first();

        $this->assertEquals(

            2,

            $log->rows_processed

        );

        $this->assertEquals(

            1,

            $log->records_created

        );

        $this->assertEquals(

            1,

            $log->failure_count

        );
    }
}
