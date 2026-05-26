<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Services\AI\Extractions\AiEtfNavExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEtfNavExtractionServiceTest extends TestCase
{
    private AiEtfNavExtractionService
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
                AiEtfNavExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_etf_nav_data()
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

                                    'nav_per_share' =>
                                    25.44,

                                    'nav_date' =>
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
            25.44,
            $extraction
                ->extracted_data['nav_per_share']
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
