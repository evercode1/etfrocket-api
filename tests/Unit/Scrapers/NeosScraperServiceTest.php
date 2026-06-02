<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\NeosScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NeosScraperServiceTest extends TestCase
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

            '*' => Http::response(

                '
                <table>

                    <tr>
                        <td>Net Assets</td>
                        <td>$12,351,859,425</td>
                    </tr>

                    <tr>
                        <td>Shares Outstanding</td>
                        <td>215,890,000</td>
                    </tr>

                    <tr>
                        <td>Net Asset Value</td>
                        <td>$57.21</td>
                    </tr>

                </table>

                <th>
                    As of: 05/29/2026
                </th>
                ',

                200

            ),

        ]);

        $security = Security::factory()
            ->make([

                'symbol' => 'QQQI',

            ]);

        $data = app(
            NeosScraperService::class
        )->extract(
            $security
        );

        $this->assertEquals(
            'QQQI',
            $data['symbol']
        );

        $this->assertEquals(
            12351859425,
            $data['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['aum_date']
        );

        $this->assertEquals(
            57.21,
            $data['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['nav_date']
        );

        $this->assertEquals(
            215890000,
            $data['shares_outstanding']
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

        $security = Security::factory()
            ->make([

                'symbol' => 'QQQI',

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'NEOS scraper could not locate fund data.'
        );

        app(
            NeosScraperService::class
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

        $security = Security::factory()
            ->make([

                'symbol' => 'QQQI',

            ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Unable to retrieve NEOS ETF page.'
        );

        app(
            NeosScraperService::class
        )->extract(
            $security
        );
    }
}
