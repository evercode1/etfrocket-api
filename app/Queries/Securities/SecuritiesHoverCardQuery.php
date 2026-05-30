<?php

namespace App\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;

class SecuritiesHoverCardQuery
{
    public function getData(string $symbol): array
    {
        $security = Security::query()

            ->select([

                'securities.id',

                'securities.symbol',

                'security_types.security_type_name',

                'security_details.security_name',

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
                strtoupper($symbol)
            )

            ->firstOrFail();

        $latestPrice = SecurityPriceHistory::query()

            ->where(
                'security_id',
                $security->id
            )

            ->orderByDesc(
                'price_date'
            )

            ->first();

        $latestDividend = SecurityDividendHistory::query()

            ->where(
                'security_id',
                $security->id
            )

            ->orderByDesc(
                'ex_dividend_date'
            )

            ->first();

        return [

            'symbol' => $security->symbol,

            'security_name' => $security->security_name,

            'security_type_name' => $security->security_type_name,

            'issuer_name' => $security->etf_issuer_name,

            'distribution_frequency_name' => $security->distribution_frequency_name,

            'last_close_price' => $latestPrice?->close_price,

            'last_close_date' => $latestPrice
                ? $latestPrice->price_date->toDateString()
                : null,

            'last_dividend_amount' => $latestDividend?->dividend_amount,

            'last_ex_dividend_date' => $latestDividend

                ? $latestDividend->ex_dividend_date->toDateString()
                : null,

            'yahoo_finance_url' => sprintf(
                'https://finance.yahoo.com/quote/%s/',
                $security->symbol
            ),

        ];
    }
}
