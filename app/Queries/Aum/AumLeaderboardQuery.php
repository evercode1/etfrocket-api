<?php

namespace App\Queries\Aum;

use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\Status;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| AUM Leaderboard
|--------------------------------------------------------------------------
|
| Latest AUM / NAV for every active security plus change and estimated
| net flows over standard periods, computed directly from
| security_aum_histories and security_nav_histories (no dependency on
| price data or the security_metrics table).
|
| Period start values use the latest row on or before the period's
| start date. A fund with no row that old (launched inside the period)
| gets null change values for that period.
|
| Estimated flow = (end shares - start shares) * end NAV, where
| shares = AUM / NAV on each date.
|
*/

class AumLeaderboardQuery
{
    public const PERIODS = ['1w', '1m', '3m', 'ytd', '1y'];

    public function getData(): array
    {
        $asOfDate = SecurityAumHistory::max('aum_date');

        if (! $asOfDate) {

            return [
                'as_of' => null,
                'periods' => self::PERIODS,
                'totals' => null,
                'securities' => [],
            ];
        }

        $asOf = CarbonImmutable::parse($asOfDate);

        $securities = $this->securities();

        $endAum = $this->valuesOnOrBefore('security_aum_histories', 'aum_date', 'assets_under_management', $asOf);

        $endNav = $this->valuesOnOrBefore('security_nav_histories', 'nav_date', 'nav_per_share', $asOf);

        $inceptionDates = SecurityAumHistory::query()
            ->selectRaw('security_id, MIN(aum_date) AS inception_date')
            ->groupBy('security_id')
            ->pluck('inception_date', 'security_id');

        $periodStarts = [];

        foreach ($this->periodStartDates($asOf) as $period => $date) {

            $periodStarts[$period] = [
                'date' => $date,
                'aum' => $this->valuesOnOrBefore('security_aum_histories', 'aum_date', 'assets_under_management', $date),
                'nav' => $this->valuesOnOrBefore('security_nav_histories', 'nav_date', 'nav_per_share', $date),
            ];
        }

        $rows = [];

        foreach ($securities as $security) {

            $end = $endAum[$security->id] ?? null;

            if (! $end) {

                continue;
            }

            $endNavValue = isset($endNav[$security->id])
                ? (float) $endNav[$security->id]['value']
                : null;

            $changes = [];

            foreach ($periodStarts as $period => $start) {

                $changes[$period] = $this->change(
                    (int) $end['value'],
                    $endNavValue,
                    isset($start['aum'][$security->id]) ? (int) $start['aum'][$security->id]['value'] : null,
                    isset($start['nav'][$security->id]) ? (float) $start['nav'][$security->id]['value'] : null,
                );
            }

            $rows[] = [
                'security_id' => $security->id,
                'symbol' => $security->symbol,
                'name' => $security->detail?->security_name,
                'issuer_id' => $security->detail?->etf_issuer_id,
                'issuer_name' => $security->detail?->issuer?->etf_issuer_name,
                'strategy_name' => $security->detail?->strategyType?->etf_strategy_type_name,
                'aum' => (int) $end['value'],
                'aum_date' => $end['date'],
                'nav' => $endNavValue,
                'inception_date' => $inceptionDates[$security->id] ?? null,
                'changes' => $changes,
            ];
        }

        usort($rows, fn ($a, $b) => $b['aum'] <=> $a['aum']);

        foreach ($rows as $index => &$row) {

            $row['rank'] = $index + 1;
        }

        unset($row);

        return [
            'as_of' => $asOf->toDateString(),
            'periods' => self::PERIODS,
            'period_start_dates' => array_map(
                fn ($start) => $start['date']->toDateString(),
                $periodStarts
            ),
            'totals' => $this->totals($rows),
            'securities' => $rows,
        ];
    }

    private function securities()
    {
        return Security::query()
            ->with([
                'detail.issuer',
                'detail.strategyType',
            ])
            ->where('status_id', Status::ACTIVE)
            ->get();
    }

    /**
     * @return array<string, CarbonImmutable>
     */
    private function periodStartDates(CarbonImmutable $asOf): array
    {
        return [
            '1w' => $asOf->subWeek(),
            '1m' => $asOf->subMonthNoOverflow(),
            '3m' => $asOf->subMonthsNoOverflow(3),
            'ytd' => $asOf->startOfYear()->subDay(),
            '1y' => $asOf->subYear(),
        ];
    }

    /**
     * Latest value per security on or before the given date.
     *
     * @return array<int, array{date: string, value: string}>
     */
    private function valuesOnOrBefore(
        string $table,
        string $dateColumn,
        string $valueColumn,
        CarbonImmutable $date
    ): array {

        $latest = DB::table($table)
            ->select('security_id')
            ->selectRaw("MAX({$dateColumn}) AS latest_date")
            ->where($dateColumn, '<=', $date->toDateString())
            ->groupBy('security_id');

        return DB::table("{$table} AS h")
            ->joinSub($latest, 'latest', function ($join) use ($dateColumn) {
                $join->on('latest.security_id', '=', 'h.security_id')
                    ->on('latest.latest_date', '=', "h.{$dateColumn}");
            })
            ->get([
                'h.security_id',
                "h.{$dateColumn} AS date",
                "h.{$valueColumn} AS value",
            ])
            ->mapWithKeys(fn ($row) => [
                (int) $row->security_id => [
                    'date' => (string) $row->date,
                    'value' => $row->value,
                ],
            ])
            ->all();
    }

    private function change(
        int $endAum,
        ?float $endNav,
        ?int $startAum,
        ?float $startNav
    ): array {

        if (is_null($startAum)) {

            return [
                'aum_change' => null,
                'aum_change_percentage' => null,
                'estimated_flow' => null,
            ];
        }

        $estimatedFlow = null;

        if ($endNav > 0 && $startNav > 0) {

            $estimatedFlow = (int) round(
                (($endAum / $endNav) - ($startAum / $startNav)) * $endNav
            );
        }

        return [
            'aum_change' => $endAum - $startAum,
            'aum_change_percentage' => $startAum > 0
                ? round((($endAum - $startAum) / $startAum) * 100, 2)
                : null,
            'estimated_flow' => $estimatedFlow,
        ];
    }

    private function totals(array $rows): array
    {
        $totalAum = array_sum(array_column($rows, 'aum'));

        $changes = [];

        foreach (self::PERIODS as $period) {

            $comparable = array_filter(
                $rows,
                fn ($row) => ! is_null($row['changes'][$period]['aum_change'])
            );

            $endAum = array_sum(array_column($comparable, 'aum'));

            $aumChange = array_sum(array_map(
                fn ($row) => $row['changes'][$period]['aum_change'],
                $comparable
            ));

            $startAum = $endAum - $aumChange;

            $changes[$period] = [
                'aum_change' => $aumChange,
                'aum_change_percentage' => $startAum > 0
                    ? round(($aumChange / $startAum) * 100, 2)
                    : null,
                'estimated_flow' => array_sum(array_map(
                    fn ($row) => $row['changes'][$period]['estimated_flow'] ?? 0,
                    $comparable
                )),
            ];
        }

        return [
            'aum' => $totalAum,
            'security_count' => count($rows),
            'changes' => $changes,
        ];
    }
}
