<?php

namespace App\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;

class CreateEtfIssuerService
{
    public function store(
        array $data
    ): EtfIssuer {
        return EtfIssuer::create([

            'etf_issuer_name' => $data['etf_issuer_name'],

            'website_url' => $data['website_url'] ?? null,

            'status_id' => $data['status_id'],

            'notes' => $data['notes'] ?? null,

        ]);
    }
}
