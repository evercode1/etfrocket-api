<?php

namespace App\Queries\Etfs;

use App\Models\Etf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompareEtfsQuery
{
    public function getData(array $resolved): array
    {
        $etfIds = $resolved['etf_ids'];
        $table = $resolved['table'];
        $dateColumn = $resolved['date_column'];
        $valueColumn = $resolved['value_column'];
        $days = $resolved['days'];

        $startDate = Carbon::now()
            ->subDays($days)
            ->toDateString();

        $etfs = Etf::query()

            ->select([
                'id',
                'symbol',
                'fund_name',
                'website_url',
            ])

            ->whereIn('id', $etfIds)

            ->get()

            ->keyBy('id');

        $rows = DB::table($table)

            ->select([
                'etf_id',
                "{$dateColumn} as comparison_date",
                "{$valueColumn} as comparison_value",
            ])

            ->whereIn('etf_id', $etfIds)

            ->whereDate($dateColumn, '>=', $startDate)

            ->whereNotNull($valueColumn)

            ->orderBy($dateColumn)

            ->get()

            ->groupBy('etf_id');

        $series = [];

        foreach ($etfIds as $etfId) {

            if (! isset($etfs[$etfId])) {
                continue;
            }

            $etf = $etfs[$etfId];

            $points = collect($rows->get($etfId, []))

                ->map(function ($row) {

                    return [
                        'date' => $row->comparison_date,
                        'value' => $row->comparison_value,
                    ];

                })

                ->values()

                ->toArray();

            $series[] = [

                'etf_id' => $etf->id,

                'symbol' => $etf->symbol,

                'fund_name' => $etf->fund_name,

                'website_url' => $etf->website_url,

                'points' => $points,

            ];
        }

        return [

            'metric' => $resolved['metric'],

            'range' => $resolved['range'],

            'series' => $series,

        ];
    }
}
