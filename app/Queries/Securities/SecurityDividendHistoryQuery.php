<?php

namespace App\Queries\Securities;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityDividendHistoryQuery
{
    public function getData(int $securityId): array
    {
        return Cache::remember(

            "security_dividend_history_{$securityId}",

            now()->addHours(6),

            function () use ($securityId) {

                return DB::table('security_dividend_histories')
                    ->join(
                        'securities',
                        'security_dividend_histories.security_id',
                        '=',
                        'securities.id'
                    )
                    ->where(
                        'security_dividend_histories.security_id',
                        $securityId
                    )
                    ->select([
                        'security_dividend_histories.id',
                        'security_dividend_histories.dividend_amount',
                        'security_dividend_histories.ex_dividend_date',
                        'security_dividend_histories.payment_date',
                    ])
                    ->orderByDesc(
                        'security_dividend_histories.ex_dividend_date'
                    )
                    ->limit(10)
                    ->get()
                    ->map(function ($row) {

                        return [
                            'id' => (int) $row->id,
                            'dividend_amount' => round(
                                (float) $row->dividend_amount,
                                4
                            ),
                            'ex_dividend_date' => $row->ex_dividend_date,
                            'payment_date' => $row->payment_date,
                        ];
                    })
                    ->toArray();
            }

        );
    }
}
