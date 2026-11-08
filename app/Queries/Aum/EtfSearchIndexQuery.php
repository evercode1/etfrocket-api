<?php

namespace App\Queries\Aum;

use App\Models\Security;
use App\Models\Status;
use App\Services\Aum\FundDataCoverage;

/*
|--------------------------------------------------------------------------
| ETF Search Index
|--------------------------------------------------------------------------
|
| Lightweight list (symbol, name, issuer, coverage status) of every ETF we
| can show a page for: active securities that either have AUM data or are
| covered by a scraper but still awaiting their first data. The client
| loads it once and filters locally.
|
*/

class EtfSearchIndexQuery
{
    public function getData(): array
    {
        $latestDates = FundDataCoverage::latestAumDates();

        $coveredIds = FundDataCoverage::coveredSecurities()
            ->pluck('securities.id')
            ->flip();

        return Security::query()
            ->with('detail.issuer')
            ->where('status_id', Status::ACTIVE)
            ->orderBy('symbol')
            ->get()
            ->filter(fn (Security $security) => isset($latestDates[$security->id])
                || isset($coveredIds[$security->id]))
            ->map(fn (Security $security) => [
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
                'status' => FundDataCoverage::status(
                    isset($coveredIds[$security->id]),
                    $latestDates[$security->id] ?? null
                ),
            ])
            ->values()
            ->all();
    }
}
