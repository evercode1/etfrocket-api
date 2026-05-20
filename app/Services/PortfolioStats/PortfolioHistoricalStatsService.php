<?php

namespace App\Services\PortfolioStats;

use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Models\PortfolioTransaction;
use Illuminate\Support\Collection;

class PortfolioHistoricalStatsService
{
    private const BUY = 1;
    private const SELL = 2;

    public function getHoldingsAsOfDate(
        int $portfolioId,
        string $asOfDate
    ): Collection {
        return PortfolioTransaction::query()
            ->selectRaw('
                etf_id,
                SUM(
                    CASE
                        WHEN transaction_type_id = ? THEN shares
                        WHEN transaction_type_id = ? THEN -shares
                        ELSE 0
                    END
                ) as shares
            ', [self::BUY, self::SELL])
            ->where('portfolio_id', $portfolioId)
            ->where('transaction_date', '<=', $asOfDate)
            ->groupBy('etf_id')
            ->having('shares', '>', 0)
            ->get()
            ->map(function ($holding) {
                return [
                    'etf_id' => (int) $holding->etf_id,
                    'shares' => round((float) $holding->shares, 4),
                ];
            });
    }

    public function getPortfolioValueAsOfDate(
        int $portfolioId,
        string $asOfDate
    ): float {
        $holdings = $this->getHoldingsAsOfDate($portfolioId, $asOfDate);

        $value = 0;

        foreach ($holdings as $holding) {
            $price = EtfPriceHistory::where('etf_id', $holding['etf_id'])
                ->where('price_date', '<=', $asOfDate)
                ->orderByDesc('price_date')
                ->value('close_price');

            if (! $price) {
                continue;
            }

            $value += (float) $holding['shares'] * (float) $price;
        }

        return round($value, 4);
    }

    public function getDividendIncomeBetweenDates(
        int $portfolioId,
        string $startDate,
        string $endDate
    ): float {
        $dividends = EtfDividendHistory::whereBetween('ex_dividend_date', [
            $startDate,
            $endDate,
        ])->get();

        $income = 0;

        foreach ($dividends as $dividend) {
            $sharesOwned = $this->getSharesOwnedAsOfDate(
                $portfolioId,
                (int) $dividend->etf_id,
                $dividend->ex_dividend_date
            );

            if ($sharesOwned <= 0) {
                continue;
            }

            $income += $sharesOwned * (float) $dividend->dividend_amount;
        }

        return round($income, 4);
    }

    public function getSharesOwnedAsOfDate(
        int $portfolioId,
        int $etfId,
        string $asOfDate
    ): float {
        $shares = PortfolioTransaction::query()
            ->where('portfolio_id', $portfolioId)
            ->where('etf_id', $etfId)
            ->where('transaction_date', '<=', $asOfDate)
            ->selectRaw('
                SUM(
                    CASE
                        WHEN transaction_type_id = ? THEN shares
                        WHEN transaction_type_id = ? THEN -shares
                        ELSE 0
                    END
                ) as shares
            ', [self::BUY, self::SELL])
            ->value('shares');

        return round((float) $shares, 4);
    }
}
