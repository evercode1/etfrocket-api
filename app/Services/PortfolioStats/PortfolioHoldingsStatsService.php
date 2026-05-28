<?php

namespace App\Services\PortfolioStats;

use App\Models\PortfolioTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioHoldingsStatsService
{
    private const BUY = 1;

    private const SELL = 2;

    public function getCurrentHoldings(int $portfolioId): Collection
    {
        return PortfolioTransaction::query()
            ->select([
                'portfolio_transactions.security_id',
                'securities.symbol',
                'security_details.security_name',
                'security_details.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = '.self::BUY.' THEN portfolio_transactions.shares
                            WHEN portfolio_transactions.transaction_type_id = '.self::SELL.' THEN -portfolio_transactions.shares
                            ELSE 0
                        END
                    ) as shares
                '),
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = '.self::BUY.' THEN portfolio_transactions.shares * portfolio_transactions.price_per_share
                            WHEN portfolio_transactions.transaction_type_id = '.self::SELL.' THEN -portfolio_transactions.shares * portfolio_transactions.price_per_share
                            ELSE 0
                        END
                    ) as cost_basis
                '),
            ])
            ->join('securities', 'portfolio_transactions.security_id', '=', 'securities.id')
            ->join('security_details', 'securities.id', '=', 'security_details.security_id')
            ->leftJoin(
                'distribution_frequencies',
                'security_details.distribution_frequency_id',
                '=',
                'distribution_frequencies.id'
            )
            ->where('portfolio_transactions.portfolio_id', $portfolioId)
            ->groupBy([
                'portfolio_transactions.security_id',
                'securities.symbol',
                'security_details.security_name',
                'security_details.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
            ])
            ->having('shares', '>', 0)
            ->get()
            ->map(function ($holding) {
                return [
                    'security_id' => (int) $holding->security_id,
                    'symbol' => $holding->symbol,
                    'security_name' => $holding->security_name,
                    'distribution_frequency_id' => (int) $holding->distribution_frequency_id,
                    'distribution_frequency_name' => $holding->distribution_frequency_name,
                    'shares' => round((float) $holding->shares, 4),
                    'cost_basis' => round((float) $holding->cost_basis, 4),
                ];
            });
    }

    public function hasCurrentHoldings(int $portfolioId): bool
    {
        return $this->getCurrentHoldings($portfolioId)->isNotEmpty();
    }

    public function getCurrentSecurityIds(int $portfolioId): array
    {
        return $this->getCurrentHoldings($portfolioId)
            ->pluck('security_id')
            ->values()
            ->toArray();
    }
}
