<?php

namespace App\Queries\Admin\Securities;

use App\Models\Security;

class ShowSecurityDataQuery
{
    public function getData(
        int $id
    ) {

        return Security::with([

            'securityType',

            'status',

            'detail.issuer',

            'detail.strategyType',

            'detail.distributionFrequency',

            'updateSchedules.updateType',

            'updateSchedules.status',

        ])

            ->findOrFail($id);

    }
}
