<?php

namespace Tests\Unit\Services\Scrapers;

use App\Models\Security;
use App\Services\Scrapers\TappAlphaScraperService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TappAlphaScraperServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();

        parent::tearDown();
    }

    /**
     * Shape of the public fund data API (trimmed; Nov 2026).
     */
    private function payload(array $latest = [], string $mode = 'live'): array
    {
        return [
            'fund' => ['ticker' => 'TDAQ'],
            'latest' => array_merge([
                'ticker' => 'TDAQ',
                'as_of_date' => '2026-11-06',
                'nav' => 23.95,
                'market_price' => 23.89,
                'net_assets' => 471343830.71,
                'shares_outstanding' => 19680000,
            ], $latest),
            'chart' => [],
            'mode' => $mode,
        ];
    }

    private function extract(string $symbol = 'TDAQ'): array
    {
        $security = Security::factory()->create(['symbol' => $symbol]);

        return app(TappAlphaScraperService::class)->extract($security);
    }

    public function test_it_extracts_fund_data_from_the_fund_api(): void
    {
        Http::fake(['*' => Http::response($this->payload(), 200)]);

        $data = $this->extract();

        $this->assertSame('TDAQ', $data['symbol']);
        $this->assertEquals(471343830.71, $data['assets_under_management']);
        $this->assertEquals(23.95, $data['nav_per_share']);
        $this->assertSame(19680000, $data['shares_outstanding']);
        $this->assertSame('2026-11-06', $data['aum_date']);
        $this->assertSame('2026-11-06', $data['nav_date']);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), config('scrapers.tappalpha.api_url'))
            && $request['ticker'] === 'TDAQ'
            && $request['view'] === 'all');
    }

    public function test_it_rejects_placeholder_data(): void
    {
        Http::fake(['*' => Http::response($this->payload(mode: 'placeholder'), 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TappAlpha fund data is not available for TDAQ.');

        $this->extract();
    }

    public function test_it_rejects_data_for_a_different_ticker(): void
    {
        Http::fake(['*' => Http::response($this->payload(['ticker' => 'TSPY']), 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TappAlpha fund data did not include TDAQ.');

        $this->extract();
    }

    public function test_it_throws_when_nav_or_net_assets_are_missing(): void
    {
        Http::fake(['*' => Http::response($this->payload(['net_assets' => null]), 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to extract TappAlpha NAV and net assets.');

        $this->extract();
    }

    public function test_it_throws_when_the_api_cannot_be_reached(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve TappAlpha fund data.');

        $this->extract();
    }
}
