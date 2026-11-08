<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Global X fund data scraper
|--------------------------------------------------------------------------
|
| Reads the fund details from https://www.globalxetfs.com/funds/{symbol}.
|
| Current page format (rendered markup, label/value pairs):
|
|   <span class="... text-muted-foreground">NAV</span> ... <span ...>$11.53</span>
|   <span class="... text-muted-foreground">Shares Outstanding</span> ... <span ...>3,870,000</span>
|   <span class="... text-muted-foreground">Net Assets</span> ... <span ...>$44.63 million</span>
|   As of<!-- --> <!-- -->Nov 6, 2026
|
| "Net Assets" is rounded (e.g. "$44.63 million"), so AUM is calculated as
| shares outstanding x NAV when both are present, which keeps the derived
| shares / flow estimates exact. Net Assets is only a fallback.
|
| The previous format (escaped JSON with ASSETS / NET_ASSET_VALUE /
| SHARES_OUTSTANDING / AS_OF_DATE) is still parsed as a fallback.
|
*/

class GlobalXScraperService
{
    public function extract(
        Security $security
    ): array {

        $symbol = strtoupper(
            $security->symbol
        );

        $url = sprintf(
            'https://www.globalxetfs.com/funds/%s',
            strtolower($symbol)
        );

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Unable to retrieve Global X ETF page.'
            );
        }

        $html = $response->body();

        $data = $this->parseRenderedDetails($html)
            ?? $this->parseLegacyJson($html);

        if (! $data) {

            throw new \RuntimeException(
                'Global X scraper could not locate fund data.'
            );
        }

        return [

            'symbol' => $symbol,

            'assets_under_management' => $data['aum'],

            'aum_date' => $data['date'],

            'nav_per_share' => $data['nav'],

            'nav_date' => $data['date'],

            'shares_outstanding' => $data['shares'],

        ];
    }

    /**
     * @return array{aum: float, nav: float, shares: ?int, date: ?string}|null
     */
    private function parseRenderedDetails(string $html): ?array
    {
        $values = $this->labelValuePairs($html);

        $nav = $this->money($values['NAV'] ?? null);

        $shares = isset($values['Shares Outstanding'])
            ? (int) preg_replace('/[^0-9]/', '', $values['Shares Outstanding'])
            : null;

        $shares = $shares > 0 ? $shares : null;

        $aum = ($nav && $shares)
            ? round($shares * $nav, 2)
            : $this->scaledMoney($values['Net Assets'] ?? null);

        if (! $nav || ! $aum) {

            return null;
        }

        return [
            'aum' => $aum,
            'nav' => $nav,
            'shares' => $shares,
            'date' => $this->asOfDate($html),
        ];
    }

    /**
     * Label => value for the fund details list. First occurrence wins
     * (NAV and Market Price appear more than once on the page).
     *
     * @return array<string, string>
     */
    private function labelValuePairs(string $html): array
    {
        preg_match_all(
            '#<span class="[^"]*">([^<]+)</span></div><div class="[^"]*"><span class="[^"]*">([^<]+)</span>#',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $values = [];

        foreach ($matches as [, $label, $value]) {

            $label = trim(html_entity_decode($label));

            $values[$label] ??= trim(html_entity_decode($value));
        }

        return $values;
    }

    /**
     * "$11.53" -> 11.53
     */
    private function money(?string $value): ?float
    {
        if ($value === null) {

            return null;
        }

        $number = (float) preg_replace('/[^0-9.]/', '', $value);

        return $number > 0 ? $number : null;
    }

    /**
     * "$44.63 million" -> 44630000.0; also handles thousand / billion.
     */
    private function scaledMoney(?string $value): ?float
    {
        if (! $value || ! preg_match('/([0-9][0-9,]*\.?[0-9]*)\s*(thousand|million|billion)?/i', $value, $m)) {

            return null;
        }

        $multiplier = match (strtolower($m[2] ?? '')) {
            'thousand' => 1_000,
            'million' => 1_000_000,
            'billion' => 1_000_000_000,
            default => 1,
        };

        $number = (float) str_replace(',', '', $m[1]) * $multiplier;

        return $number > 0 ? $number : null;
    }

    /**
     * First "As of <Mon d, yyyy>" on the page (the fund details date).
     * React inserts <!-- --> comments between the text nodes.
     */
    private function asOfDate(string $html): ?string
    {
        if (! preg_match(
            '/As of(?:\s|<!-- -->)*([A-Z][a-z]{2,8}\.? \d{1,2}, \d{4})/',
            $html,
            $m
        )) {

            return null;
        }

        try {

            return Carbon::parse($m[1])->toDateString();
        } catch (\Throwable) {

            return null;
        }
    }

    /**
     * Previous page format: escaped JSON in the Next.js payload.
     *
     * @return array{aum: float, nav: float, shares: ?int, date: ?string}|null
     */
    private function parseLegacyJson(string $html): ?array
    {
        preg_match('/\\\\\\"ASSETS\\\\\\":([0-9\.]+)/', $html, $aumMatch);

        preg_match('/\\\\\\"NET_ASSET_VALUE\\\\\\":\\\\\\"([0-9\.]+)\\\\\\"/', $html, $navMatch);

        preg_match('/\\\\\\"SHARES_OUTSTANDING\\\\\\":([0-9]+)/', $html, $sharesMatch);

        preg_match('/\\\\\"AS_OF_DATE\\\\\":\\\\\"\$D([0-9]{4}-[0-9]{2}-[0-9]{2})/', $html, $dateMatch);

        if (! isset($aumMatch[1], $navMatch[1])) {

            return null;
        }

        return [
            'aum' => (float) $aumMatch[1],
            'nav' => (float) $navMatch[1],
            'shares' => isset($sharesMatch[1]) ? (int) $sharesMatch[1] : null,
            'date' => isset($dateMatch[1]) ? Carbon::parse($dateMatch[1])->toDateString() : null,
        ];
    }
}
