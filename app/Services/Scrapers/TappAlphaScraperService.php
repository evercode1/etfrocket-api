<?php

namespace App\Services\Scrapers;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TappAlphaScraperService
{
    public function extract(Security $security): array
    {
        $html = Http::timeout(30)
            ->get($security->detail->website_url)
            ->body();

        $date = $this->extractAsOfDate($html);

        return [

            'symbol' => $security->symbol,

            'assets_under_management' => $this->extractAum($html),

            'aum_date' => $date,

            'nav_per_share' => $this->extractNav($html),

            'nav_date' => $date,

            'shares_outstanding' => $this->extractSharesOutstanding($html),

        ];
    }

    protected function extractAsOfDate(string $html): string
    {
        preg_match(
            '/([A-Za-z]+\s+\d{1,2},\s+\d{4})/',
            $html,
            $matches
        );

        if (! isset($matches[1])) {

            throw new \Exception(
                'Unable to extract TappAlpha as-of date.'
            );
        }

        return Carbon::parse(
            $matches[1]
        )->toDateString();
    }

    protected function extractNav(string $html): float
    {
        preg_match(
            '/NAV Price<\/div>.*?<div class="heading-style-h6">([\d\.]+)<\/div>/s',
            $html,
            $matches
        );

        if (! isset($matches[1])) {
            throw new \Exception('Unable to extract TappAlpha NAV.');
        }

        return (float) $matches[1];
    }

    protected function extractAum(string $html): float
    {
        preg_match(
            '/Net Assets<\/div>.*?data-format-number="true" class="heading-style-h6">([\d,]+)<\/div>/s',
            $html,
            $matches
        );

        if (! isset($matches[1])) {
            throw new \Exception('Unable to extract TappAlpha AUM.');
        }

        return (float) str_replace(
            ',',
            '',
            $matches[1]
        );
    }

    protected function extractSharesOutstanding(string $html): int
    {
        preg_match(
            '/Shares Outstanding<\/div>.*?<div data-format-number="true" class="table-text">([\d,]+)<\/div>/s',
            $html,
            $matches
        );

        if (! isset($matches[1])) {
            throw new \Exception('Unable to extract TappAlpha shares outstanding.');
        }

        return (int) str_replace(
            ',',
            '',
            $matches[1]
        );
    }
}
