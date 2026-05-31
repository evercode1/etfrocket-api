<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\RexSharesScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RexSharesScraperServiceTest extends TestCase
{
    private RexSharesScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->service =
            app(
                RexSharesScraperService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'NVII',

                ]);

        $html = '

            <div id="fund-stats">

                <div class="as_of_date">
                    As of 05/28/2026
                </div>

                <div class="t-row">

                    <div class="t-col t-label">
                        NAV
                    </div>

                    <div class="t-col t-data">
                        $26.57
                    </div>

                </div>

                <div class="t-row">

                    <div class="t-col t-label">
                        Fund Assets
                    </div>

                    <div class="t-col t-data">
                        $101,193,600.00
                    </div>

                </div>

                <div class="t-row">

                    <div class="t-col t-label">
                        Shares Outstanding
                    </div>

                    <div class="t-col t-data">
                        3,810,000
                    </div>

                </div>

            </div>

        ';

        Http::fake([

            '*' => Http::response(
                $html,
                200
            ),

        ]);

        $result =

            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            'NVII',
            $result['symbol']
        );

        $this->assertEquals(
            101193600,
            $result['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-28',
            $result['aum_date']
        );

        $this->assertEquals(
            26.57,
            $result['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-28',
            $result['nav_date']
        );

        $this->assertEquals(
            3810000,
            $result['shares_outstanding']
        );
    }

    public function test_it_throws_exception_when_fund_data_is_missing(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'NVII',

                ]);

        Http::fake([

            '*' => Http::response(

                '<html>No Fund Stats</html>',

                200

            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'REX Shares scraper could not locate fund data.'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_page_cannot_be_retrieved(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'NVII',

                ]);

        Http::fake([

            '*' => Http::response(
                [],
                500
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Failed to retrieve REX Shares ETF page.'
        );

        $this->service
            ->extract(
                $security
            );
    }
}
