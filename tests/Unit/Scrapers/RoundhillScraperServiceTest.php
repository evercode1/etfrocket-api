<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\RoundhillScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoundhillScraperServiceTest extends TestCase
{
    private RoundhillScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->service =
            app(
                RoundhillScraperService::class
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

    public function test_it_extracts_fund_data()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'AMDW',

                ]);

        $csv =

            'Fund Name,Fund Ticker,CUSIP,Net Assets,Shares Outstanding,NAV,NAV Change Dollars,NAV Change Percentage,Market Price,Market Price Change Dollars,Market Price Change Percentage,Premium/Discount,Rate Date'."\n".

            'Roundhill AMD WeeklyPay ETF,AMDW,77926X783,148357191.93,2700000,54.95,0.05,0.10,55.00,0.06,0.11,0.09,05/29/2026';

        Http::fake([

            '*' => Http::response(
                $csv,
                200
            ),

        ]);

        $result =

            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            'AMDW',
            $result['symbol']
        );

        $this->assertEquals(
            148357192,
            $result['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['aum_date']
        );

        $this->assertEquals(
            54.95,
            $result['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['nav_date']
        );

        $this->assertEquals(
            2700000,
            $result['shares_outstanding']
        );
    }

    public function test_it_throws_exception_when_symbol_is_not_found()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'AMDW',

                ]);

        $csv =

            'Fund Name,Fund Ticker,CUSIP,Net Assets,Shares Outstanding,NAV,Rate Date'."\n".

            'Roundhill Something Else ETF,OTHER,123456789,1000000,10000,10.00,05/29/2026';

        Http::fake([

            '*' => Http::response(
                $csv,
                200
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Roundhill fund data not found for symbol: AMDW'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_csv_is_empty()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'AMDW',

                ]);

        Http::fake([

            '*' => Http::response(
                '',
                200
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Roundhill CSV is empty.'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_csv_cannot_be_retrieved()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'AMDW',

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
            'Failed to retrieve Roundhill fund data.'
        );

        $this->service
            ->extract(
                $security
            );
    }
}
