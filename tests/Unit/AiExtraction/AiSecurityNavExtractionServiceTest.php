<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Services\AI\Extractions\AiSecurityNavExtractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSecurityNavExtractionServiceTest extends TestCase
{
    private AiSecurityNavExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service = app(AiSecurityNavExtractionService::class);
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_security_nav_data()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        Security::factory()->create([
            'symbol' => 'NVII',

        ]);

        $security = Security::firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => json_encode([

                                    'symbol' => $security->symbol,

                                    'nav_per_share' => 25.44,

                                    'nav_date' => now()->toDateString(),

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
            25.44,
            $extraction
                ->extracted_data['nav_per_share']
        );
    }

    public function test_it_throws_exception_on_failed_response()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        Security::factory()->create([
            'symbol' => 'NVII',

        ]);
        $security = Security::firstOrFail();

        Http::fake([

            'https://api.openai.com/v1/responses' => Http::response([], 500),

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
