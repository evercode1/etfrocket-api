<?php

namespace App\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;

class UpdateEtfIssuerService
{
    public function update(
        int $id,
        array $data
    ): EtfIssuer {
        $issuer =
            EtfIssuer::findOrFail(
                $id
            );

        $issuer->update([

            'etf_issuer_name' => $data['etf_issuer_name'],

            'website_url' => $data['website_url'] ?? null,

            'status_id' => $data['status_id'],

            'notes' => $data['notes'] ?? null,

        ]);

        return $issuer->fresh();
    }
}
