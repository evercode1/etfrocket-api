<?php

namespace Tests\Feature;

use App\Models\Security;
use App\Services\Scrapers\KurvScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KurvScraperServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_fund_data()
    {
        Http::fake([

            '*' => Http::response([

                'lastTradingDate' => '2026-05-29T21:00:00.000Z',

                'Ticker' => 'KQQQ',

                'NAVCents' => 3146.62,

                'NetAssets' => 126494016,

                'Holdings' => [

                    [

                        'MarketValueCents' => 6000000000,

                    ],

                    [

                        'MarketValueCents' => 6649401600,

                    ],

                ],

            ], 200),

        ]);

        $security =
            Security::factory()
                ->create([

                    'symbol' => 'KQQQ',

                ]);

        $data = app(
            KurvScraperService::class
        )->extract(
            $security
        );

        $this->assertEquals(
            'KQQQ',
            $data['symbol']
        );

        $this->assertEquals(
            31.4662,
            $data['nav_per_share']
        );

        $this->assertEquals(
            126494016,
            $data['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['aum_date']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['nav_date']
        );
    }

    public function test_it_throws_exception_when_fund_data_is_missing()
    {
        Http::fake([

            '*' => Http::response([

                'Ticker' => 'KQQQ',

            ], 200),

        ]);

        $security =
            Security::factory()
                ->create([

                    'symbol' => 'KQQQ',

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Kurv fund data is incomplete.'
        );

        app(
            KurvScraperService::class
        )->extract(
            $security
        );
    }

    public function test_it_extracts_kurv_fund_data_live(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'KQQQ',

                ]);

        $result =

            app(
                KurvScraperService::class
            )->extract(
                $security
            );

        $this->assertEquals(
            'KQQQ',
            $result['symbol']
        );

        $this->assertNotNull(
            $result['assets_under_management']
        );

        $this->assertGreaterThan(
            100000000,
            $result['assets_under_management']
        );

        $this->assertNotNull(
            $result['aum_date']
        );

        $this->assertNotNull(
            $result['nav_per_share']
        );

        $this->assertGreaterThan(
            1,
            $result['nav_per_share']
        );

        $this->assertNotNull(
            $result['nav_date']
        );

        $this->assertNotNull(
            $result['shares_outstanding']
        );

        $this->assertGreaterThan(
            1000000,
            $result['shares_outstanding']
        );

        $this->assertEquals(

            round(

                $result['assets_under_management']

                /

                $result['nav_per_share']

            ),

            $result['shares_outstanding']

        );
    }

    public function test_it_throws_exception_when_page_cannot_be_retrieved()
    {
        Http::fake([

            '*' => Http::response(
                '',
                500
            ),

        ]);

        $security =
            Security::factory()
                ->create([

                    'symbol' => 'KQQQ',

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Failed to retrieve Kurv ETF data.'
        );

        app(
            KurvScraperService::class
        )->extract(
            $security
        );
    }

    public function test_live_kurv_api()
    {
        $response = Http::get(
            'https://web.services.kurvinvest.com/etfdata/KQQQ/latest_price.json'
        );

        $this->assertTrue(
            $response->successful()
        );

        $json = $response->json();

        $this->assertEquals(
            'KQQQ',
            $json['Ticker']
        );

        $this->assertArrayHasKey(
            'NAVCents',
            $json
        );

        $this->assertArrayHasKey(
            'Holdings',
            $json
        );
    }
}
