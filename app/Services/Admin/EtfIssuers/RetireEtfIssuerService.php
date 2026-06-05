<?php

namespace App\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;

class RetireEtfIssuerService
{
    public function retire(
        int $id
    ): EtfIssuer {
        $issuer =
            EtfIssuer::findOrFail(
                $id
            );

        $issuer->update([

            'status_id' => Status::RETIRED,

        ]);

        return $issuer->fresh();
    }
}
