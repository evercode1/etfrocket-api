<?php

namespace App\Queries\Securities;

use App\Models\Security;
use Illuminate\Support\Facades\Cache;

class SecuritySummaryQuery
{
    public function getData(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        return Cache::remember(

            "security_summary_{$symbol}",

            now()->addDay(),

            function () use ($symbol) {

                $security = Security::query()

                    ->select([

                        'securities.id',

                        'securities.symbol',

                        'security_types.security_type_name',

                        'security_details.security_name',

                        'security_details.expense_ratio',

                        'security_details.website_url',

                        'distribution_frequencies.distribution_frequency_name',

                        'etf_issuers.etf_issuer_name',

                    ])

                    ->join(
                        'security_details',
                        'securities.id',
                        '=',
                        'security_details.security_id'
                    )

                    ->leftJoin(
                        'security_types',
                        'securities.security_type_id',
                        '=',
                        'security_types.id'
                    )

                    ->leftJoin(
                        'distribution_frequencies',
                        'security_details.distribution_frequency_id',
                        '=',
                        'distribution_frequencies.id'
                    )

                    ->leftJoin(
                        'etf_issuers',
                        'security_details.etf_issuer_id',
                        '=',
                        'etf_issuers.id'
                    )

                    ->where(
                        'securities.symbol',
                        $symbol
                    )

                    ->firstOrFail();

                return [

                    'id' => $security->id,

                    'symbol' => $security->symbol,

                    'security_name' => $security->security_name,

                    'security_type_name' => $security->security_type_name,

                    'issuer_name' => $security->etf_issuer_name,

                    'distribution_frequency_name' => $security->distribution_frequency_name,

                    'expense_ratio' => $security->expense_ratio,

                    'website_url' => $security->website_url,

                    'yahoo_finance_url' => sprintf(
                        'https://finance.yahoo.com/quote/%s/',
                        $security->symbol
                    ),

                ];
            }

        );
    }
}
