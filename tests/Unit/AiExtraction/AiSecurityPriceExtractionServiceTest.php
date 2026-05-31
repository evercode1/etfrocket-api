<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Services\AI\Extractions\AiSecurityPriceExtractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSecurityPriceExtractionServiceTest extends TestCase
{
    private AiSecurityPriceExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service =
            app(
                AiSecurityPriceExtractionService::class
            );

        config()->set(
            'services.twelve_data.api_key',
            'test-api-key'
        );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_security_price_data()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([
            'api.twelvedata.com/*' => Http::response([
                'symbol' => 'CHPY',
                'close' => '24.51',
                'datetime' => now()->toDateString(),
                'volume' => '125000',
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
            $security->id,
            $extraction->security_id
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

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);
        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $date =
            now()->toDateString();

        Http::fake([
            'api.twelvedata.com/*' => Http::response([
                'symbol' => 'CHPY',
                'close' => '24.51',
                'datetime' => now()->toDateString(),
                'volume' => '125000',
            ], 200),
        ]);

        $extraction =
            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            $date,
            $extraction
                ->extracted_data['price_date']
        );
    }

    public function test_it_stores_volume()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([
            'api.twelvedata.com/*' => Http::response([
                'symbol' => 'CHPY',
                'close' => '24.51',
                'datetime' => now()->toDateString(),
                'volume' => '125000',
            ], 200),
        ]);

        $extraction =
            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            125000,
            $extraction->extracted_data['volume']
        );
    }

    public function test_it_stores_record_in_database()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([
            'api.twelvedata.com/*' => Http::response([
                'symbol' => 'CHPY',
                'close' => '24.51',
                'datetime' => now()->toDateString(),
                'volume' => '125000',
            ], 200),
        ]);

        $this->service
            ->extract(
                $security
            );

        $this->assertDatabaseCount(
            'ai_data_extractions',
            1
        );
    }

    public function test_it_throws_exception_on_failed_response()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',

        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        Http::fake([

            'api.twelvedata.com/*' => Http::response([

                'error' => [

                    'message' => 'Bad request',

                ],

            ], 500),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(

            'Twelve Data request failed.'

        );

        $this->service
            ->extract(
                $security
            );
    }
}
