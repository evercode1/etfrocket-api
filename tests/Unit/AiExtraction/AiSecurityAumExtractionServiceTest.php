<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Services\AI\Extractions\AiSecurityAumExtractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSecurityAumExtractionServiceTest extends TestCase
{
    private AiSecurityAumExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service =
            app(
                AiSecurityAumExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_etf_aum_data()
    {
        $security = Security::factory()->create([

            'symbol' => 'NVII',

        ]);

        Http::fake([

            'https://api.openai.com/v1/responses' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => json_encode([

                                    'symbol' => $security->symbol,

                                    'assets_under_management' => 1250000000,

                                    'aum_date' => now()->toDateString(),

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
                    $security
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
        $security = Security::factory()->create([

            'symbol' => 'NVII',

        ]);

        Http::fake([

            'https://api.openai.com/v1/responses' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => 'INVALID_JSON',

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
                $security
            );
    }
}
