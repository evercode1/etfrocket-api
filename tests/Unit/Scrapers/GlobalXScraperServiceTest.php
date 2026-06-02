<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Services\Scrapers\GlobalXScraperService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GlobalXScraperServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Security::truncate();
        SecurityDetail::truncate();

    }

    protected function tearDown(): void
    {

        Security::truncate();
        SecurityDetail::truncate();

        parent::tearDown();
    }

    public function test_it_extracts_fund_data()
    {
        Http::fake([

            '*' => Http::response(
                '
            {
                \"ETF_DETAILS\":{
                    \"ASSETS\":2829947160.45,
                    \"AS_OF_DATE\":\"$D2026-05-29T00:00:00.000Z\",
                    \"NET_ASSET_VALUE\":\"100.35\",
                    \"SHARES_OUTSTANDING\":28199931
                }
            }
            ',
                200
            ),

        ]);

        $security = Security::factory()->create([

            'symbol' => 'CLIP',

        ]);

        $data = app(
            GlobalXScraperService::class
        )->extract(
            $security
        );

        $this->assertEquals(
            'CLIP',
            $data['symbol']
        );

        $this->assertEquals(
            100.35,
            $data['nav_per_share']
        );

        $this->assertEquals(
            2829947160.45,
            $data['assets_under_management']
        );

        $this->assertEquals(
            28199931,
            $data['shares_outstanding']
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

            '*' => Http::response(
                '<html>No ETF Data</html>',
                200
            ),

        ]);

        $security = Security::factory()->create([

            'symbol' => 'CLIP',

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Global X scraper could not locate fund data.'
        );

        app(
            GlobalXScraperService::class
        )->extract(
            $security
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

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Unable to retrieve Global X ETF page.'
        );

        $security = Security::factory()->create([

            'symbol' => 'CLIP',

        ]);

        app(
            GlobalXScraperService::class
        )->extract(
            $security
        );
    }
}
