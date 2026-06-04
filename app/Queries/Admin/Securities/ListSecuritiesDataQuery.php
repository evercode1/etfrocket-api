<?php

namespace App\Queries\Admin\Securities;

use App\Models\Security;
use Illuminate\Http\Request;

class ListSecuritiesDataQuery
{
    public function getData(Request $request)
    {
        $query =

            Security::query()

                ->select([

                    'securities.id',

                    'securities.symbol',

                    'securities.security_type_id',

                    'securities.status_id',

                    'security_details.security_name',

                    'security_details.etf_issuer_id',

                    'security_details.etf_strategy_type_id',

                    'security_details.distribution_frequency_id',

                    'security_details.expense_ratio',

                    'security_details.website_url',

                ])

                ->leftJoin(

                    'security_details',

                    'securities.id',

                    '=',

                    'security_details.security_id'

                )

                ->with([

                    'securityType:id,security_type_name',

                    'status:id,status_name',

                    'detail.issuer:id,etf_issuer_name',

                    'detail.strategyType:id,etf_strategy_type_name',

                    'detail.distributionFrequency:id,distribution_frequency_name',

                    'updateSchedules',

                ])
                ->withCount(

                    'updateSchedules'

                );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (

            $request->filled(
                'search'
            )

        ) {

            $search =

                $request->input(
                    'search'
                );

            $query->where(

                function ($query) use (

                    $search

                ) {

                    $query

                        ->where(

                            'securities.symbol',

                            'like',

                            "%{$search}%"

                        )

                        ->orWhere(

                            'security_details.security_name',

                            'like',

                            "%{$search}%"

                        );

                }

            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if (

            $request->filled(
                'status_id'
            )

        ) {

            $query->where(

                'securities.status_id',

                $request->input(
                    'status_id'
                )

            );
        }

        return $query

            ->orderBy(

                'securities.symbol'

            )

            ->paginate(

                $request->input(
                    'per_page',
                    25
                )

            )

            ->through(

                function (
                    Security $security
                ) {

                    return [

                        'id' => $security->id,

                        'symbol' => $security->symbol,

                        'security_type' => $security
                            ->securityType
                            ?->security_type_name,

                        'status' => $security
                            ->status
                            ?->status_name,

                        'security_name' => $security
                            ->detail
                            ?->security_name,

                        'issuer' => $security
                            ->detail
                            ?->issuer
                            ?->etf_issuer_name,

                        'strategy' => $security
                            ->detail
                            ?->strategyType
                            ?->etf_strategy_type_name,

                        'distribution_frequency' => $security
                            ->detail
                            ?->distributionFrequency
                            ?->distribution_frequency_name,
                        'schedule_count' => $security->update_schedules_count,

                    ];

                }

            );
    }
}
