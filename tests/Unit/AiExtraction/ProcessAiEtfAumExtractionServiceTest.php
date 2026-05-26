<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Services\AI\Extractions\ProcessAiEtfAumExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiEtfAumExtractionServiceTest extends TestCase
{
    private ProcessAiEtfAumExtractionService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                ProcessAiEtfAumExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_aum_extraction()
    {
        $etf =
            Etf::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'data_source_id' =>
                DataSource::MANUAL_ENTRY,

                'extracted_data' => [

                    'symbol' =>
                    $etf->symbol,

                    'assets_under_management' =>
                    1000000000,

                    'aum_date' =>
                    now()->toDateString(),

                ],

            ]);

        $result =
            $this->service
            ->process(
                $extraction
            );

        $this->assertTrue(
            $result->is_validated
        );

        $this->assertDatabaseHas(
            'etf_aum_histories',
            [

                'assets_under_management' =>
                1000000000,

            ]
        );
    }

    public function test_it_fails_if_aum_date_is_stale()
    {
        $etf =
            Etf::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'extracted_data' => [

                    'symbol' =>
                    $etf->symbol,

                    'assets_under_management' =>
                    1000000000,

                    'aum_date' =>
                    now()
                        ->subDays(30)
                        ->toDateString(),

                ],

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->service
            ->process(
                $extraction
            );
    }
}
