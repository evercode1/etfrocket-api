<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Services\AI\Extractions\AiEtfPriceExtractionService;
use Database\Seeders\EtfSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEtfPriceExtractionServiceTest extends TestCase
{
    private AiEtfPriceExtractionService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        $this->seed(
            EtfSeeder::class
        );

        $this->service =
            app(
                AiEtfPriceExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('etfs')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_etf_price_data()
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

                                    'close_price' => 24.51,

                                    'price_date' => now()
                                        ->toDateString(),

                                    'volume' => 125000,

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
            $etf->id,
            $extraction->etf_id
        );

        $this->assertEquals(
            'CHPY',
            $extraction->extracted_data['symbol']
        );

        $this->assertEquals(
            24.51,
            $extraction->extracted_data['close_price']
        );
    }

    public function test_it_stores_price_date()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $date =
            now()->toDateString();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => json_encode([

                                    'symbol' => 'CHPY',

                                    'close_price' => 21.12,

                                    'price_date' => $date,

                                    'volume' => 150000,

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

        $this->assertEquals(
            $date,
            $extraction
                ->extracted_data['price_date']
        );
    }

    public function test_it_stores_volume()
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

                                    'close_price' => 22.33,

                                    'price_date' => now()
                                        ->toDateString(),

                                    'volume' => 987654,

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

        $this->assertEquals(
            987654,
            $extraction
                ->extracted_data['volume']
        );
    }

    public function test_it_stores_record_in_database()
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

                                    'close_price' => 20.99,

                                    'price_date' => now()
                                        ->toDateString(),

                                    'volume' => 1000,

                                ]),

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $this->service
            ->extract(
                $etf
            );

        $this->assertDatabaseCount(
            'ai_data_extractions',
            1
        );
    }

    public function test_it_throws_exception_on_failed_response()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' =>

            Http::response([

                'error' => [

                    'message' =>
                    'Bad request',

                ],

            ], 500),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'AI ETF price extraction failed.'
        );

        $this->service
            ->extract(
                $etf
            );
    }

    public function test_it_throws_exception_on_invalid_json()
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

        $this->expectExceptionMessage(
            'AI ETF price extraction returned invalid JSON.'
        );

        $this->service
            ->extract(
                $etf
            );
    }
}
