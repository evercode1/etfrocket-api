<?php

namespace App\Queries\MissionControl;

use App\Models\EtfDividendHistory;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\PerformanceRangeType;
use Illuminate\Support\Facades\DB;

class PortfolioSnapshotQuery
{
    public function getData(int $portfolio_id): ?array
    {
        $portfolio = Portfolio::where('id', $portfolio_id)
            ->first();

        if (! $portfolio) {
            return null;
        }

        $holdings = PortfolioTransaction::query()
            ->select([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
                'etfs.distribution_frequency_id',
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = 1 THEN portfolio_transactions.shares
                            WHEN portfolio_transactions.transaction_type_id = 2 THEN -portfolio_transactions.shares
                            ELSE 0
                        END
                    ) as shares
                '),
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = 1 THEN portfolio_transactions.shares * portfolio_transactions.price_per_share
                            WHEN portfolio_transactions.transaction_type_id = 2 THEN -portfolio_transactions.shares * portfolio_transactions.price_per_share
                            ELSE 0
                        END
                    ) as cost_basis
                '),
            ])
            ->join('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')
            ->where('portfolio_transactions.portfolio_id', $portfolio->id)
            ->groupBy([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
                'etfs.distribution_frequency_id',
            ])
            ->having('shares', '>', 0)
            ->get();

        if ($holdings->isEmpty()) {
            return [
                'portfolio_id' => $portfolio->id,
                'portfolio_name' => $portfolio->portfolio_name,
                'portfolio_value' => 0,
                'cost_basis' => 0,
                'unrealized_gain_loss' => 0,
                'total_return_percentage' => null,
                'monthly_income' => 0,
                'nav_health' => 'No Holdings',
                'holdings' => [],
                'holdings_count' => 0,
                'has_holdings' => false,
            ];
        }

        $holdingRows = [];

        $portfolioValue = 0;
        $costBasis = 0;
        $projectedMonthlyIncome = 0;

        foreach ($holdings as $holding) {
            $latestPrice = EtfPriceHistory::where('etf_id', $holding->etf_id)
                ->orderByDesc('price_date')
                ->value('close_price');

            $latestMetric = EtfMetric::where('etf_id', $holding->etf_id)
                ->where('performance_range_type_id', PerformanceRangeType::MAX)
                ->first();

            $recentDividends = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->orderByDesc('ex_dividend_date')
                ->limit(4)
                ->pluck('dividend_amount');

            $averageDividend = $recentDividends->isNotEmpty()
                ? $recentDividends->avg()
                : 0;

            $monthlyMultiplier = $this->getMonthlyDistributionMultiplier(
                $holding->distribution_frequency_id
            );

            $shares = (float) $holding->shares;
            $holdingCostBasis = (float) $holding->cost_basis;
            $currentPrice = (float) ($latestPrice ?? 0);
            $marketValue = round($shares * $currentPrice, 4);

            $estimatedMonthlyIncome = round(
                $shares * (float) $averageDividend * $monthlyMultiplier,
                4
            );

            $portfolioValue += $marketValue;
            $costBasis += $holdingCostBasis;
            $projectedMonthlyIncome += $estimatedMonthlyIncome;

            $holdingRows[] = [
                'etf_id' => $holding->etf_id,
                'symbol' => $holding->symbol,
                'fund_name' => $holding->fund_name,
                'distribution_frequency_id' => $holding->distribution_frequency_id,
                'shares' => round($shares, 4),
                'cost_basis' => round($holdingCostBasis, 4),
                'latest_price' => $latestPrice ? round((float) $latestPrice, 4) : null,
                'market_value' => $marketValue,
                'average_dividend' => round((float) $averageDividend, 4),
                'monthly_distribution_multiplier' => round($monthlyMultiplier, 4),
                'estimated_monthly_income' => $estimatedMonthlyIncome,
                'total_return_percentage' => $latestMetric?->total_return_percentage,
                'nav_erosion_percentage' => $latestMetric?->nav_erosion_percentage,
            ];
        }

        $unrealizedGainLoss = round($portfolioValue - $costBasis, 4);

        $totalReturnPercentage = $costBasis > 0
            ? round(($unrealizedGainLoss / $costBasis) * 100, 4)
            : null;

        return [
            'portfolio_id' => $portfolio->id,
            'portfolio_name' => $portfolio->portfolio_name,
            'portfolio_value' => round($portfolioValue, 4),
            'cost_basis' => round($costBasis, 4),
            'unrealized_gain_loss' => $unrealizedGainLoss,
            'total_return_percentage' => $totalReturnPercentage,
            'monthly_income' => round($projectedMonthlyIncome, 4),
            'nav_health' => $this->getNavHealth($holdingRows),
            'holdings' => $holdingRows,
            'holdings_count' => count($holdingRows),
            'has_holdings' => count($holdingRows) > 0,
        ];
    }

    private function getMonthlyDistributionMultiplier(?int $distributionFrequencyId): float
    {
        return match ((int) $distributionFrequencyId) {
            1 => 30.0,
            2 => 52 / 12,
            3 => 26 / 12,
            4 => 1.0,
            5 => 1 / 3,
            6 => 1 / 6,
            7 => 1 / 12,
            8 => 1.0,
            9 => 0.0,
            default => 1.0,
        };
    }

    private function getNavHealth(array $holdings): string
    {
        $navValues = collect($holdings)
            ->pluck('nav_erosion_percentage')
            ->filter(fn($value) => ! is_null($value));

        if ($navValues->isEmpty()) {
            return 'Unknown';
        }

        if ($navValues->min() < -10) {
            return 'Watch';
        }

        if ($navValues->min() < -3) {
            return 'Mixed';
        }

        return 'Stable';
    }
}
