<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Services\AI\Extractions\ProcessAiEtfDividendExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiEtfDividendExtractionServiceTest extends TestCase
{
    private ProcessAiEtfDividendExtractionService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                ProcessAiEtfDividendExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_dividend_extraction()
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

                    'dividend_amount' =>
                    0.25,

                    'ex_dividend_date' =>
                    now()->toDateString(),

                    'payment_date' =>
                    now()->addDays(7)->toDateString(),

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
            'etf_dividend_histories',
            [

                'dividend_amount' =>
                0.25,

            ]
        );
    }

    public function test_it_fails_if_symbol_does_not_match()
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
                    'WRONG',

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
