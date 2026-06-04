<?php

namespace App\Queries\Admin\Securities;

use App\Models\Security;

class ListSecuritiesDataQuery
{
    public function getData()
    {
        return Security::query()

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

            ->orderBy(

                'securities.symbol'

            )

            ->paginate(
                request(
                    'per_page',
                    25
                )
            );
    }
}
