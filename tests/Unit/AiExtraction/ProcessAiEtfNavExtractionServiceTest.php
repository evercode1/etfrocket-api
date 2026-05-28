<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Etf;
use App\Services\AI\Extractions\ProcessAiEtfNavExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiEtfNavExtractionServiceTest extends TestCase
{
    private ProcessAiEtfNavExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                ProcessAiEtfNavExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_nav_extraction()
    {
        $etf =
            Etf::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'etf_id' => $etf->id,

                    'data_source_id' => DataSource::MANUAL_ENTRY,

                    'extracted_data' => [

                        'symbol' => $etf->symbol,

                        'nav_per_share' => 25.55,

                        'nav_date' => now()->toDateString(),

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
            'etf_nav_histories',
            [

                'nav_per_share' => 25.55,

            ]
        );
    }

    public function test_it_fails_if_symbol_is_missing()
    {
        $etf =
            Etf::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'etf_id' => $etf->id,

                    'extracted_data' => [

                        'nav_per_share' => 25.55,

                        'nav_date' => now()->toDateString(),

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
