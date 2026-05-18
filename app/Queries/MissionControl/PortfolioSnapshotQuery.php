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
                DB::raw('SUM(portfolio_transactions.shares) as shares'),
                DB::raw('SUM(portfolio_transactions.shares * portfolio_transactions.price_per_share) as cost_basis'),
            ])
            ->join('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')
            ->where('portfolio_transactions.portfolio_id', $portfolio->id)
            ->where('portfolio_transactions.transaction_type_id', 1)
            ->groupBy([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
            ])
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

            $averageDividend = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->orderByDesc('ex_dividend_date')
                ->limit(4)
                ->avg('dividend_amount');

            $shares = (float) $holding->shares;
            $holdingCostBasis = (float) $holding->cost_basis;
            $currentPrice = (float) ($latestPrice ?? 0);
            $marketValue = round($shares * $currentPrice, 4);

            $estimatedMonthlyIncome = round($shares * (float) ($averageDividend ?? 0) * 4, 4);

            $portfolioValue += $marketValue;
            $costBasis += $holdingCostBasis;
            $projectedMonthlyIncome += $estimatedMonthlyIncome;

            $holdingRows[] = [
                'etf_id' => $holding->etf_id,
                'symbol' => $holding->symbol,
                'fund_name' => $holding->fund_name,
                'shares' => round($shares, 4),
                'cost_basis' => round($holdingCostBasis, 4),
                'latest_price' => $latestPrice ? round((float) $latestPrice, 4) : null,
                'market_value' => $marketValue,
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
        ];
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
