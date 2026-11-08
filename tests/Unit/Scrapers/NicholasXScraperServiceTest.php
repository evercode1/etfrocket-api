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

    public function test_it_discovers_the_post_id_for_unconfigured_funds()
    {
        \Illuminate\Support\Facades\Cache::forget('scrapers:nicholasx:post-id:NEWX');

        $security = Security::factory()->create(['symbol' => 'NEWX']);

        Http::fake([
            'nicholasx.com/wp-json/twm/v1/data*' => Http::sequence()
                ->push(['html' => '<table><tr><td>Net Assets</td><td>$6.00m</td></tr><tr><td>NAV</td><td>$29.99</td></tr><tr><td>Shares Outstanding</td><td>200,000</td></tr></table>'], 200)
                ->push(['html' => 'As of: 11/05/2026'], 200),
            // Markup from nicholasx.com fund pages (Nov 2026).
            'nicholasx.com/newx/' => Http::response(
                '<link rel="alternate" type="application/json" href="https://nicholasx.com/wp-json/wp/v2/pages/4242" /><link rel="shortlink" href="https://nicholasx.com/?p=4242" />',
                200
            ),
        ]);

        $result = $this->service->extract($security);

        $this->assertEquals(6000000, $result['assets_under_management']);
        $this->assertEquals(29.99, $result['nav_per_share']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'twm/v1/data')
            && (int) $request['post_id'] === 4242);

        $this->assertSame(4242, \Illuminate\Support\Facades\Cache::get('scrapers:nicholasx:post-id:NEWX'));

        \Illuminate\Support\Facades\Cache::forget('scrapers:nicholasx:post-id:NEWX');
    }

    public function test_it_reports_when_an_unconfigured_post_id_cannot_be_found()
    {
        $security = Security::factory()->create(['symbol' => 'GONE']);

        Http::fake([
            'nicholasx.com/gone/' => Http::response('Not found', 404),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No NicholasX post ID configured for symbol: GONE, and it could not be found on the fund page.');

        $this->service->extract($security);
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
