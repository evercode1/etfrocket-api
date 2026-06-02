<?php

namespace Tests\Unit\Services\Scrapers;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Services\Scrapers\TappAlphaScraperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TappAlphaScraperServiceTest extends TestCase
{
    private TappAlphaScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->service =
            app(
                TappAlphaScraperService::class
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

    public function test_extract_returns_expected_tdaq_data(): void
    {
        $security =
            Security::create([

                'symbol' => 'TDAQ',

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'website_url' => 'https://www.tappalphafunds.com/etfs/tdaq',

        ]);

        $html = <<<'HTML'
<div class="as_of-wrap">
    <p class="text-size-medium">As of:</p>
    <p class="text-size-medium">May 29, 2026</p>
</div>

<div>
    <div>NAV Price</div>
    <div class="heading-style-h6">28.87</div>
</div>

<div>
    <div>Net Assets</div>
    <div data-format-number="true" class="heading-style-h6">210763410</div>
</div>

<div>
    <div>Shares Outstanding</div>
    <div data-format-number="true" class="table-text">7260000</div>
</div>
HTML;

        Http::fake([

            'www.tappalphafunds.com/*' => Http::response(
                $html,
                200
            ),

        ]);

        $data =
            $this->service
                ->extract(
                    $security
                );

        $this->assertEquals(
            'TDAQ',
            $data['symbol']
        );

        $this->assertEquals(
            210763410,
            $data['assets_under_management']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['aum_date']
        );

        $this->assertEquals(
            28.87,
            $data['nav_per_share']
        );

        $this->assertEquals(
            '2026-05-29',
            $data['nav_date']
        );

        $this->assertEquals(
            7260000,
            $data['shares_outstanding']
        );
    }

    public function test_it_throws_exception_when_data_cannot_be_found(): void
    {
        $security =
            Security::create([

                'symbol' => 'TDAQ',

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'website_url' => 'https://www.tappalphafunds.com/etfs/tdaq',

        ]);

        Http::fake([

            'www.tappalphafunds.com/*' => Http::response(
                '<html>No ETF Data</html>',
                200
            ),

        ]);

        $this->expectException(
            \Exception::class
        );

        $this->expectExceptionMessage(
            'Unable to extract TappAlpha as-of date.'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_page_cannot_be_retrieved(): void
    {
        $security =
            Security::create([

                'symbol' => 'TDAQ',

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'website_url' => 'https://www.tappalphafunds.com/etfs/tdaq',

        ]);

        Http::fake([

            'www.tappalphafunds.com/*' => Http::response(
                [],
                500
            ),

        ]);

        $this->expectException(
            \Exception::class
        );

        $this->service
            ->extract(
                $security
            );
    }
}
