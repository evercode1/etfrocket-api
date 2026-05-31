<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\YieldMaxScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YieldMaxScraperServiceTest extends TestCase
{
    private YieldMaxScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        $this->service =
            app(
                YieldMaxScraperService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_extracts_fund_data()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        $html = <<<'HTML'
<div class="fund-row">
    <div class="fund-label">Net Assets:</div>
    <div class="fund-value">$1.02B</div>
</div>

<div class="fund-row">
    <div class="fund-label">NAV:</div>
    <div class="fund-value">$80.80</div>
</div>

<div class="fund-row">
    <div class="fund-label">Shares Outstanding:</div>
    <div class="fund-value">12,650,000</div>
</div>

<p>As of 05/29/2026</p>
HTML;

        Http::fake([

            'yieldmaxetfs.com/*' => Http::response(
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
            'CHPY',
            $result['symbol']
        );

        $this->assertEquals(
            1020000000,
            $result['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['aum_date']
        );

        $this->assertEquals(
            80.80,
            $result['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['nav_date']
        );

        $this->assertEquals(
            12650000,
            $result['shares_outstanding']
        );
    }

    public function test_it_throws_exception_when_fund_data_cannot_be_found()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        Http::fake([

            'yieldmaxetfs.com/*' => Http::response(
                '<html>No Fund Data</html>',
                200
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'YieldMax scraper could not locate fund data.'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_page_cannot_be_retrieved()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        Http::fake([

            'yieldmaxetfs.com/*' => Http::response(
                [],
                500
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Failed to retrieve YieldMax ETF page.'
        );

        $this->service
            ->extract(
                $security
            );
    }
}
