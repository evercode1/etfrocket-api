<?php

namespace App\Queries\Aum;

use App\Models\Security;
use App\Models\Status;

/*
|--------------------------------------------------------------------------
| ETF Search Index
|--------------------------------------------------------------------------
|
| Lightweight list (symbol, name, issuer) of active securities that have
| AUM data, for the site-wide search box. The client loads it once and
| filters locally.
|
*/

class EtfSearchIndexQuery
{
    public function getData(): array
    {
        return Security::query()
            ->with('detail.issuer')
            ->where('status_id', Status::ACTIVE)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_aum_histories')
                    ->whereColumn('security_aum_histories.security_id', 'securities.id');
            })
            ->orderBy('symbol')
            ->get()
            ->map(fn (Security $security) => [
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
            ])
            ->values()
            ->all();
    }
}
