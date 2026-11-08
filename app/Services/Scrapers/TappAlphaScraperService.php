<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| TappAlpha fund data scraper
|--------------------------------------------------------------------------
|
| tappalphafunds.com fund pages no longer contain the figures in their
| HTML: they render placeholders ("XX.XX") and a script fills them from the
| public fund data API configured in config('scrapers.tappalpha.api_url').
| We read the same API:
|
|   GET {api_url}?ticker=TDAQ&view=all
|
|   {
|     "mode": "live",
|     "latest": {
|       "ticker": "TDAQ",
|       "as_of_date": "2026-11-06",
|       "nav": 23.95,
|       "net_assets": 471343830.71,
|       "shares_outstanding": 19680000
|     },
|     ...
|   }
|
| net_assets is exact (not rounded), so it is used directly as AUM.
|
*/

class TappAlphaScraperService
{
    public function extract(Security $security): array
    {
        $symbol = strtoupper($security->symbol);

        $response = Http::timeout(30)
            ->acceptJson()
            ->get(
                config('scrapers.tappalpha.api_url'),
                [
                    'ticker' => $symbol,
                    'view' => 'all',
                ]
            );

        if (! $response->successful()) {

            throw new \RuntimeException(
                'Unable to retrieve TappAlpha fund data.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['mode'] ?? null) === 'placeholder') {

            throw new \RuntimeException(
                'TappAlpha fund data is not available for '.$symbol.'.'
            );
        }

        $latest = $payload['latest'] ?? null;

        if (! is_array($latest) || strtoupper($latest['ticker'] ?? '') !== $symbol) {

            throw new \RuntimeException(
                'TappAlpha fund data did not include '.$symbol.'.'
            );
        }

        $nav = $this->positive($latest['nav'] ?? null);

        $aum = $this->positive($latest['net_assets'] ?? null);

        if ($nav === null || $aum === null) {

            throw new \RuntimeException(
                'Unable to extract TappAlpha NAV and net assets.'
            );
        }

        $shares = $this->positive($latest['shares_outstanding'] ?? null);

        $date = isset($latest['as_of_date'])
            ? Carbon::parse($latest['as_of_date'])->toDateString()
            : null;

        return [
            'symbol' => $symbol,
            'assets_under_management' => $aum,
            'aum_date' => $date,
            'nav_per_share' => $nav,
            'nav_date' => $date,
            'shares_outstanding' => $shares === null ? null : (int) $shares,
        ];
    }

    private function positive(mixed $value): ?float
    {
        if (! is_numeric($value)) {

            return null;
        }

        $number = (float) $value;

        return $number > 0 ? $number : null;
    }
}
