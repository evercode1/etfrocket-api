<?php

namespace Tests\Unit\Scrapers;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Services\Scrapers\GlobalXScraperService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GlobalXScraperServiceTest extends TestCase
{
    /*
    | Markup copied from https://www.globalxetfs.com/funds/alty (Nov 2026):
    | the NAV headline with its "As of" date, and the fund details list.
    */
    private const CURRENT_PAGE = <<<'HTML'
<div class="flex flex-col"><div class="flex items-center gap-1"><span class="text-xs font-semibold whitespace-nowrap text-background-warm">NAV</span></div><div class="flex items-center gap-0.5"><span class="font-display text-[40px] leading-[50px] font-semibold text-white lg:text-[48px] lg:leading-[48px]">$11.53</span></div><span class="text-xs text-subtle-foreground">As of<!-- --> <!-- -->Nov 6, 2026</span></div>
<ul>
<li class="flex flex-col gap-1 border-b border-border-muted py-3"><div class="flex items-center justify-between gap-4"><div class="flex min-w-0 items-center gap-1"><span class="text-xs font-semibold text-muted-foreground">Net Assets</span></div><div class="flex items-center gap-6"><span class="text-sm font-bold text-background text-right">$44.63 million</span></div></div></li>
<li class="flex flex-col gap-1 border-b border-border-muted py-3"><div class="flex items-center justify-between gap-4"><div class="flex min-w-0 items-center gap-1"><span class="text-xs font-semibold text-muted-foreground">Shares Outstanding</span></div><div class="flex items-center gap-6"><span class="text-sm font-bold text-background text-right">3,870,000</span></div></div></li>
</ul>
HTML;

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

    private function extract(string $symbol = 'ALTY'): array
    {
        $security = Security::factory()->create(['symbol' => $symbol]);

        return app(GlobalXScraperService::class)->extract($security);
    }

    public function test_it_extracts_fund_data_from_the_current_page_format(): void
    {
        Http::fake(['*' => Http::response(self::CURRENT_PAGE, 200)]);

        $data = $this->extract();

        $this->assertSame('ALTY', $data['symbol']);
        $this->assertEquals(11.53, $data['nav_per_share']);
        $this->assertSame(3_870_000, $data['shares_outstanding']);

        // Exact shares x NAV, not the rounded "$44.63 million".
        $this->assertEquals(44_621_100.0, $data['assets_under_management']);

        $this->assertSame('2026-11-06', $data['aum_date']);
        $this->assertSame('2026-11-06', $data['nav_date']);
    }

    public function test_it_falls_back_to_net_assets_when_shares_are_missing(): void
    {
        $page = preg_replace('#<li[^>]*>(?:(?!</li>).)*Shares Outstanding.*?</li>#s', '', self::CURRENT_PAGE);

        Http::fake(['*' => Http::response($page, 200)]);

        $data = $this->extract();

        $this->assertNull($data['shares_outstanding']);
        $this->assertEquals(44_630_000.0, $data['assets_under_management']);
    }

    public function test_it_still_reads_the_previous_json_format(): void
    {
        Http::fake([
            '*' => Http::response('
            {
                \"ETF_DETAILS\":{
                    \"ASSETS\":2829947160.45,
                    \"AS_OF_DATE\":\"$D2026-05-29T00:00:00.000Z\",
                    \"NET_ASSET_VALUE\":\"100.35\",
                    \"SHARES_OUTSTANDING\":28199931
                }
            }
            ', 200),
        ]);

        $data = $this->extract('CLIP');

        $this->assertSame('CLIP', $data['symbol']);
        $this->assertEquals(100.35, $data['nav_per_share']);
        $this->assertEquals(2829947160.45, $data['assets_under_management']);
        $this->assertSame(28199931, $data['shares_outstanding']);
        $this->assertSame('2026-05-29', $data['aum_date']);
    }

    public function test_it_throws_exception_when_fund_data_is_missing(): void
    {
        Http::fake(['*' => Http::response('<html>No ETF Data</html>', 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Global X scraper could not locate fund data.');

        $this->extract('CLIP');
    }

    public function test_it_throws_exception_when_page_cannot_be_retrieved(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve Global X ETF page.');

        $this->extract('CLIP');
    }
}
