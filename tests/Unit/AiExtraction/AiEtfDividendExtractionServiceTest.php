<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Services\AI\Extractions\AiEtfDividendExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEtfDividendExtractionServiceTest extends TestCase
{
    private AiEtfDividendExtractionService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                AiEtfDividendExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_etf_dividend_data()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => json_encode([

                                    'symbol' => 'CHPY',

                                    'dividend_amount' => 0.35,

                                    'ex_dividend_date' => now()
                                        ->toDateString(),

                                    'payment_date' => now()
                                        ->addDays(7)
                                        ->toDateString(),

                                ]),

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $extraction =
            $this->service
            ->extract(
                $etf
            );

        $this->assertInstanceOf(
            AiDataExtraction::class,
            $extraction
        );

        $this->assertEquals(
            0.35,
            $extraction
                ->extracted_data['dividend_amount']
        );
    }

    public function test_it_throws_exception_on_failed_response()
    {
        $etf =
            Etf::firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([], 500),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->service
            ->extract(
                $etf
            );
    }
}
