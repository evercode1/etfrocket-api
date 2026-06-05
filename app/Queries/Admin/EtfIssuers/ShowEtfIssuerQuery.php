<?php

namespace App\Queries\Admin\EtfIssuers;

use App\Models\EtfIssuer;

class ShowEtfIssuerQuery
{
    public function getData(
        int $id
    ) {
        return EtfIssuer::with([

            'status',

        ])

            ->findOrFail(
                $id
            );
    }
}
