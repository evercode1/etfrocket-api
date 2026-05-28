<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use App\Services\AI\Extractions\ProcessAiSecurityDividendExtractionService;
use Database\Seeders\SecuritySeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiSecurityDividendExtractionServiceTest extends TestCase
{
    private ProcessAiSecurityDividendExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        $this->seed(
            SecuritySeeder::class
        );

        $this->service =
            app(
                ProcessAiSecurityDividendExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_dividend_extraction()
    {
        $security =
            Security::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::MANUAL_ENTRY,

                    'extracted_data' => [

                        'symbol' => $security->symbol,

                        'dividend_amount' => 0.25,

                        'ex_dividend_date' => now()->toDateString(),

                        'payment_date' => now()->addDays(7)->toDateString(),

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
            'security_dividend_histories',
            [

                'dividend_amount' => 0.25,

            ]
        );
    }

    public function test_it_fails_if_symbol_does_not_match()
    {
        $security =
            Security::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'WRONG',

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
