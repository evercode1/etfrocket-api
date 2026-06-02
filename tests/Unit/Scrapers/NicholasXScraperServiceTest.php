<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\NicholasXScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NicholasXScraperServiceTest extends TestCase
{
    private NicholasXScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        $this->service =
            app(
                NicholasXScraperService::class
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

    public function test_it_extracts_blox_fund_data()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'BLOX',

                ]);

        Http::fake([

            'nicholasx.com/wp-json/twm/v1/data*' => Http::sequence()
                ->push([

                    'html' => '
                            <table>
                                <tr>
                                    <td>Net Assets</td>
                                    <td>$331.30m</td>
                                </tr>
                                <tr>
                                    <td>NAV</td>
                                    <td>$17.65</td>
                                </tr>
                                <tr>
                                    <td>Shares Outstanding</td>
                                    <td>18,775,000</td>
                                </tr>
                            </table>
                        ',

                ], 200)
                ->push([

                    'html' => 'As of: 05/29/2026',

                ], 200),

        ]);

        $result =

            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            'BLOX',
            $result['symbol']
        );

        $this->assertEquals(
            331300000,
            $result['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['aum_date']
        );

        $this->assertEquals(
            17.65,
            $result['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['nav_date']
        );

        $this->assertEquals(
            18775000,
            $result['shares_outstanding']
        );
    }

    public function test_it_throws_exception_when_fund_data_cannot_be_found()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'BLOX',

                ]);

        Http::fake([

            'nicholasx.com/wp-json/twm/v1/data*' => Http::response([

                'html' => '<table></table>',

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

    public function test_it_throws_exception_when_endpoint_fails()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'BLOX',

                ]);

        Http::fake([

            'nicholasx.com/wp-json/twm/v1/data*' => Http::response(
                [],
                500
            ),

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_nicholasx_page_ids_are_valid()
    {

        $map = config('scrapers.nicholasx.page_ids');

        foreach ($map as $symbol => $postId) {

            $response = Http::get(
                'https://nicholasx.com/wp-json/twm/v1/data',
                [
                    'type' => 'fund-info-table',
                    'post_id' => $postId,
                ]
            );

            $this->assertTrue(
                $response->successful(),
                "{$symbol} page id {$postId} returned {$response->status()}"
            );

            $html = $response->json()['html'] ?? '';

            preg_match(
                '/Ticker<\/td>\s*<td>([^<]+)<\/td>/i',
                $html,
                $matches
            );

            $this->assertNotEmpty(
                $matches,
                "Could not locate ticker for {$symbol}"
            );

            $this->assertEquals(
                strtoupper($symbol),
                strtoupper(trim($matches[1])),
                "Configured page id {$postId} is not {$symbol}"
            );
        }
    }

    public function test_it_converts_billion_aum_values()
    {
        Http::fake([

            'https://nicholasx.com/wp-json/twm/v1/data*' => function ($request) {

                if ($request['type'] === 'daily-nav-table') {

                    return Http::response([

                        'html' => '
                        <table>
                            <tr>
                                <td>Net Assets</td>
                                <td>$1.25b</td>
                            </tr>
                            <tr>
                                <td>NAV</td>
                                <td>$25.50</td>
                            </tr>
                            <tr>
                                <td>Shares Outstanding</td>
                                <td>49,019,608</td>
                            </tr>
                        </table>
                    ',

                    ], 200);
                }

                if ($request['type'] === 'date-nav') {

                    return Http::response([

                        'html' => 'As of: 05/29/2026',

                    ], 200);
                }

                return Http::response([], 404);
            },

        ]);

        config()->set(

            'scrapers.nicholasx.page_ids.BLOX',

            1916

        );

        $security = Security::factory()->create([

            'symbol' => 'BLOX',

        ]);

        $result =

            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            'BLOX',
            $result['symbol']
        );

        $this->assertEquals(
            1250000000,
            $result['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['aum_date']
        );

        $this->assertEquals(
            25.50,
            $result['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $result['nav_date']
        );

        $this->assertEquals(
            49019608,
            $result['shares_outstanding']
        );
    }
}
