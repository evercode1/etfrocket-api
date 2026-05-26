<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfPriceHistory;
use App\Services\AI\Extractions\ProcessAiEtfPriceExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiEtfPriceExtractionServiceTest extends TestCase
{
    private ProcessAiEtfPriceExtractionService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                ProcessAiEtfPriceExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_price_extraction()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'data_source_id' =>
                DataSource::MANUAL_ENTRY,

                'extracted_data' => [

                    'symbol' =>
                    'CHPY',

                    'close_price' =>
                    25.44,

                    'price_date' =>
                    now()->toDateString(),

                    'volume' =>
                    250000,

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
            'etf_price_histories',
            [

                'etf_id' =>
                $etf->id,

                'close_price' =>
                25.44,

                'volume' =>
                250000,

            ]
        );
    }

    public function test_it_updates_existing_price_record()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        EtfPriceHistory::create([

            'etf_id' =>
            $etf->id,

            'price_date' =>
            now()->toDateString(),

            'close_price' =>
            10.00,

            'volume' =>
            1000,

            'data_source_id' =>
            DataSource::MANUAL_ENTRY,

            'retrieved_at' =>
            now(),

        ]);

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'data_source_id' =>
                DataSource::MANUAL_ENTRY,

                'extracted_data' => [

                    'symbol' =>
                    'CHPY',

                    'close_price' =>
                    44.55,

                    'price_date' =>
                    now()->toDateString(),

                    'volume' =>
                    999999,

                ],

            ]);

        $this->service
            ->process(
                $extraction
            );

        $this->assertDatabaseCount(
            'etf_price_histories',
            1
        );

        $this->assertDatabaseHas(
            'etf_price_histories',
            [

                'close_price' =>
                44.55,

                'volume' =>
                999999,

            ]
        );
    }

    public function test_it_fails_if_symbol_is_missing()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'extracted_data' => [

                    'close_price' =>
                    10.22,

                    'price_date' =>
                    now()->toDateString(),

                    'volume' =>
                    1000,

                ],

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Extracted symbol is missing.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_symbol_does_not_match()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'extracted_data' => [

                    'symbol' =>
                    'WRONG',

                    'close_price' =>
                    10.22,

                    'price_date' =>
                    now()->toDateString(),

                    'volume' =>
                    1000,

                ],

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Extracted symbol does not match ETF symbol.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_price_date_is_stale()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'extracted_data' => [

                    'symbol' =>
                    'CHPY',

                    'close_price' =>
                    12.55,

                    'price_date' =>
                    now()
                        ->subDays(10)
                        ->toDateString(),

                    'volume' =>
                    1000,

                ],

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'price_date is stale.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_marks_extraction_as_failed()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
            ->create([

                'etf_id' =>
                $etf->id,

                'extracted_data' => [

                    'symbol' =>
                    'WRONG',

                    'close_price' =>
                    12.55,

                    'price_date' =>
                    now()->toDateString(),

                    'volume' =>
                    1000,

                ],

            ]);

        try {

            $this->service
                ->process(
                    $extraction
                );
        } catch (\Throwable $e) {

            //
        }

        $extraction->refresh();

        $this->assertFalse(
            $extraction->is_validated
        );

        $this->assertNotNull(
            $extraction->failed_at
        );

        $this->assertNotNull(
            $extraction->failure_reason
        );
    }
}
