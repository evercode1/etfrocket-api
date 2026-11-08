<?php

namespace App\Queries\Aum;

use App\Models\Security;
use App\Models\Status;
use App\Services\Aum\FundDataCoverage;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| AUM History (single security)
|--------------------------------------------------------------------------
|
| Full daily AUM history for one active security, joined with NAV on the
| same date, plus derived shares outstanding (AUM / NAV) and estimated
| daily net flow ((shares - previous shares) * NAV).
|
| Returns null when the symbol is unknown, not active, or not covered
| (no scraper/schedule and no AUM data). Covered ETFs without data yet
| return coverage_status "pending" and an empty history.
|
*/

class AumHistoryQuery
{
    public function getData(string $symbol): ?array
    {
        $security = Security::query()
            ->with([
                'detail.issuer',
                'detail.strategyType',
            ])
            ->where('symbol', strtoupper($symbol))
            ->where('status_id', Status::ACTIVE)
            ->first();

        if (! $security) {

            return null;
        }

        $rows = DB::table('security_aum_histories AS aum')
            ->leftJoin('security_nav_histories AS nav', function ($join) {
                $join->on('nav.security_id', '=', 'aum.security_id')
                    ->on('nav.nav_date', '=', 'aum.aum_date');
            })
            ->where('aum.security_id', $security->id)
            ->orderBy('aum.aum_date')
            ->get([
                'aum.aum_date AS date',
                'aum.assets_under_management AS aum',
                'nav.nav_per_share AS nav',
            ]);

        $history = [];

        $previousShares = null;

        foreach ($rows as $row) {

            $aum = (int) $row->aum;

            $nav = is_null($row->nav) ? null : (float) $row->nav;

            $shares = $nav > 0 ? (int) round($aum / $nav) : null;

            $flow =
                (! is_null($shares) && ! is_null($previousShares))

                    ? (int) round(($shares - $previousShares) * $nav)

                    : null;

            $history[] = [
                'date' => (string) $row->date,
                'aum' => $aum,
                'nav' => $nav,
                'shares' => $shares,
                'flow' => $flow,
            ];

            if (! is_null($shares)) {

                $previousShares = $shares;
            }
        }

        $latest = end($history) ?: null;

        $coverageStatus = FundDataCoverage::status(
            FundDataCoverage::isCovered($security),
            $latest['date'] ?? null
        );

        if ($coverageStatus === FundDataCoverage::NOT_COVERED) {

            return null;
        }

        return [
            'coverage_status' => $coverageStatus,
            'stale_after_days' => FundDataCoverage::STALE_AFTER_DAYS,
            'security' => [
                'id' => $security->id,
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
                'strategy_name' => $security->detail?->strategyType?->etf_strategy_type_name,
                'website_url' => $security->detail?->website_url,
                'inception_date' => $history[0]['date'] ?? null,
            ],
            'latest' => $latest,
            'history' => $history,
        ];
    }
}
