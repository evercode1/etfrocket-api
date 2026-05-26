<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Services\AI\Extractions\AiEtfAumExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEtfAumExtractionServiceTest extends TestCase
{
    private AiEtfAumExtractionService
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
                AiEtfAumExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_etf_aum_data()
    {
        $etf =
            Etf::firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => json_encode([

                                    'symbol' =>
                                    $etf->symbol,

                                    'assets_under_management' =>
                                    1250000000,

                                    'aum_date' =>
                                    now()->toDateString(),

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
            1250000000,
            $extraction
                ->extracted_data['assets_under_management']
        );
    }

    public function test_it_throws_exception_on_invalid_json()
    {
        $etf =
            Etf::firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>
                                'INVALID_JSON',

                            ],

                        ],

                    ],

                ],

            ], 200),

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
