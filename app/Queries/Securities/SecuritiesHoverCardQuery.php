<?php

namespace App\Queries\Securities;

use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use Illuminate\Support\Facades\Cache;

class SecuritiesHoverCardQuery
{
    public function getData(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        return Cache::remember(

            "security_hover_card_{$symbol}",

            now()->addHours(6),

            function () use ($symbol) {

                $security = app(
                    SecuritySummaryQuery::class
                )->getData($symbol);

                $latestPrice = SecurityPriceHistory::query()

                    ->where(
                        'security_id',
                        $security['id']
                    )

                    ->orderByDesc(
                        'price_date'
                    )

                    ->first();

                $latestDividend = SecurityDividendHistory::query()

                    ->where(
                        'security_id',
                        $security['id']
                    )

                    ->orderByDesc(
                        'ex_dividend_date'
                    )

                    ->first();

                return [

                    ...$security,

                    'last_close_price' => $latestPrice?->close_price,

                    'last_close_date' => $latestPrice
                            ? $latestPrice
                                ->price_date
                                ->toDateString()
                            : null,

                    'last_dividend_amount' => $latestDividend?->dividend_amount,

                    'last_ex_dividend_date' => $latestDividend
                            ? $latestDividend
                                ->ex_dividend_date
                                ->toDateString()
                            : null,

                ];
            }

        );
    }
}
