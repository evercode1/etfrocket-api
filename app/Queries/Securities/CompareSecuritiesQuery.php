<?php

namespace App\Queries\Securities;

use App\Models\Security;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompareSecuritiesQuery
{
    public function getData(array $resolved): array
    {
        $securityIds = $resolved['security_ids'];
        $table = $resolved['table'];
        $dateColumn = $resolved['date_column'];
        $valueColumn = $resolved['value_column'];
        $days = $resolved['days'];

        $startDate = Carbon::now()
            ->subDays($days)
            ->toDateString();

        $securities = Security::query()

            ->select([
                'securities.id',
                'securities.symbol',
                'security_details.security_name',
                'security_details.website_url',
            ])

            ->leftJoin('security_details', 'securities.id', '=', 'security_details.security_id')

            ->whereIn('securities.id', $securityIds)

            ->get()

            ->keyBy('id');

        $rows = DB::table($table)

            ->select([
                'security_id',
                "{$dateColumn} as comparison_date",
                "{$valueColumn} as comparison_value",
            ])

            ->whereIn('security_id', $securityIds)

            ->whereDate($dateColumn, '>=', $startDate)

            ->whereNotNull($valueColumn)

            ->orderBy($dateColumn)

            ->get()

            ->groupBy('security_id');

        $series = [];

        foreach ($securityIds as $securityId) {

            if (! isset($securities[$securityId])) {
                continue;
            }

            $security = $securities[$securityId];

            $points = collect($rows->get($securityId, []))

                ->map(function ($row) {

                    return [
                        'date' => $row->comparison_date,
                        'value' => $row->comparison_value,
                    ];

                })

                ->values()

                ->toArray();

            $series[] = [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

                'security_name' => $security->security_name,

                'website_url' => $security->website_url,

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
