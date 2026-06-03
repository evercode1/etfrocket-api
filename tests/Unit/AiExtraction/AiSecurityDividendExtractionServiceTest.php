<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Services\AI\Extractions\AiSecurityDividendExtractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSecurityDividendExtractionServiceTest extends TestCase
{
    private AiSecurityDividendExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service =
            app(
                AiSecurityDividendExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_security_dividend_data()
    {

        Security::factory()->create([

            'symbol' => 'CHPY',

        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        config()->set(
            'services.twelve_data.api_key',
            'test-api-key'
        );

        Http::fake([

            'api.twelvedata.com/*' => Http::response([

                'meta' => [
                    'symbol' => 'CHPY',
                ],

                'dividends' => [

                    [

                        'ex_date' => now()->toDateString(),

                        'amount' => 0.35,

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
            0.35,
            $extraction
                ->extracted_data['dividend_amount']
        );
    }

    public function test_it_throws_exception_on_failed_response()
    {

        Security::factory()->create([

            'symbol' => 'CHPY',

        ]);

        $security =
            Security::firstOrFail();

        Http::fake([

            'api.twelvedata.com/*' => Http::response([], 500),

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
