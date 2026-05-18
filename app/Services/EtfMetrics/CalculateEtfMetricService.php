<?php

namespace App\Services\EtfMetrics;

use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfDividendHistory;
use App\Models\EtfMetric;
use App\Models\EtfNavHistory;
use App\Models\EtfPriceHistory;
use App\Models\MetricDirection;
use App\Models\PerformanceRangeType;
use Illuminate\Support\Facades\Log;

class CalculateEtfMetricService
{
    public function calculate(Etf $etf, int $performance_range_type_id): ?EtfMetric
    {
        $endDate = now()->toDateString();

        $startDate = $this->getStartDate(
            $performance_range_type_id,
            $etf->id
        );

        $startPrice = $this->getStartPrice($etf->id, $startDate);
        $endPrice = $this->getEndPrice($etf->id, $endDate);

        $startNav = $this->getStartNav($etf->id, $startDate);
        $endNav = $this->getEndNav($etf->id, $endDate);

        $startAum = $this->getStartAum($etf->id, $startDate);
        $endAum = $this->getEndAum($etf->id, $endDate);

        if (
            is_null($startPrice) ||
            is_null($endPrice)
        ) {
            Log::warning('Skipping ETF metric calculation due to missing price data.', [
                'etf_id' => $etf->id,
                'symbol' => $etf->symbol,
                'performance_range_type_id' => $performance_range_type_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'has_start_price' => ! is_null($startPrice),
                'has_end_price' => ! is_null($endPrice),
            ]);

            return null;
        }

        $priceChange = $this->calculateRawChange($startPrice, $endPrice);
        $priceChangePercentage = $this->calculatePercentageChange($startPrice, $endPrice);

        $dividendsPaid = $this->getDividendsPaid($etf->id, $startDate, $endDate);
        $dividendCount = $this->getDividendCount($etf->id, $startDate, $endDate);

        $averageDividend = $dividendCount > 0
            ? round($dividendsPaid / $dividendCount, 4)
            : null;

        $totalReturnPercentage = $this->calculateTotalReturnPercentage(
            $startPrice,
            $endPrice,
            $dividendsPaid
        );

        $navChange = $this->calculateRawChange($startNav, $endNav);

        $navErosionPercentage = $this->calculateNavErosionPercentage(
            $startNav,
            $endNav,
            $dividendsPaid
        );

        $navDirectionId = $this->getNavDirectionId($navErosionPercentage);

        $aumChange = $this->calculateRawChange($startAum, $endAum);
        $aumChangePercentage = $this->calculatePercentageChange($startAum, $endAum);

        $aumDirectionId = $this->getAumDirectionId($aumChangePercentage);

        return EtfMetric::updateOrCreate(
            [
                'etf_id' => $etf->id,
                'performance_range_type_id' => $performance_range_type_id,
            ],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,

                'start_price' => $startPrice,
                'end_price' => $endPrice,
                'price_change' => $priceChange,
                'price_change_percentage' => $priceChangePercentage,

                'dividends_paid' => $dividendsPaid,
                'dividend_count' => $dividendCount,
                'average_dividend' => $averageDividend,

                'total_return_percentage' => $totalReturnPercentage,

                'start_nav' => $startNav,
                'end_nav' => $endNav,
                'nav_change' => $navChange,
                'nav_erosion_percentage' => $navErosionPercentage,
                'nav_direction_id' => $navDirectionId,

                'start_aum' => $startAum,
                'end_aum' => $endAum,
                'aum_change' => $aumChange,
                'aum_change_percentage' => $aumChangePercentage,
                'aum_direction_id' => $aumDirectionId,

                'calculated_at' => now(),
            ]
        );
    }

    private function getStartDate(int $performance_range_type_id, int $etf_id): ?string
    {
        return match ($performance_range_type_id) {
            PerformanceRangeType::FIVE_DAY => now()->subDays(5)->toDateString(),
            PerformanceRangeType::THIRTY_DAY => now()->subDays(30)->toDateString(),
            PerformanceRangeType::NINETY_DAY => now()->subDays(90)->toDateString(),
            PerformanceRangeType::YEAR_TO_DATE => now()->startOfYear()->toDateString(),
            PerformanceRangeType::ONE_YEAR => now()->subYear()->toDateString(),
            PerformanceRangeType::MAX => $this->getMaxStartDate($etf_id),
            default => now()->subDays(30)->toDateString(),
        };
    }

    private function getMaxStartDate(int $etf_id): ?string
    {
        return EtfPriceHistory::where('etf_id', $etf_id)
            ->orderBy('price_date', 'asc')
            ->value('price_date');
    }

    private function getStartPrice(int $etf_id, ?string $startDate): ?float
    {
        $query = EtfPriceHistory::where('etf_id', $etf_id);

        if ($startDate) {
            $query->where('price_date', '>=', $startDate);
        }

        return $query->orderBy('price_date', 'asc')->value('close_price');
    }

    private function getEndPrice(int $etf_id, string $endDate): ?float
    {
        return EtfPriceHistory::where('etf_id', $etf_id)
            ->where('price_date', '<=', $endDate)
            ->orderBy('price_date', 'desc')
            ->value('close_price');
    }

    private function getDividendsPaid(int $etf_id, ?string $startDate, string $endDate): float
    {
        $query = EtfDividendHistory::where('etf_id', $etf_id)
            ->where('ex_dividend_date', '<=', $endDate);

        if ($startDate) {
            $query->where('ex_dividend_date', '>=', $startDate);
        }

        return round((float) $query->sum('dividend_amount'), 4);
    }

    private function getDividendCount(int $etf_id, ?string $startDate, string $endDate): int
    {
        $query = EtfDividendHistory::where('etf_id', $etf_id)
            ->where('ex_dividend_date', '<=', $endDate);

        if ($startDate) {
            $query->where('ex_dividend_date', '>=', $startDate);
        }

        return $query->count();
    }

    private function getStartNav(int $etf_id, ?string $startDate): ?float
    {
        $query = EtfNavHistory::where('etf_id', $etf_id);

        if ($startDate) {
            $query->where('nav_date', '>=', $startDate);
        }

        return $query->orderBy('nav_date', 'asc')->value('nav_per_share');
    }

    private function getEndNav(int $etf_id, string $endDate): ?float
    {
        return EtfNavHistory::where('etf_id', $etf_id)
            ->where('nav_date', '<=', $endDate)
            ->orderBy('nav_date', 'desc')
            ->value('nav_per_share');
    }

    private function getStartAum(int $etf_id, ?string $startDate): ?int
    {
        $query = EtfAumHistory::where('etf_id', $etf_id);

        if ($startDate) {
            $query->where('aum_date', '>=', $startDate);
        }

        return $query->orderBy('aum_date', 'asc')->value('assets_under_management');
    }

    private function getEndAum(int $etf_id, string $endDate): ?int
    {
        return EtfAumHistory::where('etf_id', $etf_id)
            ->where('aum_date', '<=', $endDate)
            ->orderBy('aum_date', 'desc')
            ->value('assets_under_management');
    }

    private function calculateRawChange(null|float|int $start, null|float|int $end): null|float|int
    {
        if (is_null($start) || is_null($end)) {
            return null;
        }

        return round($end - $start, 4);
    }

    private function calculatePercentageChange(null|float|int $start, null|float|int $end): ?float
    {
        if (is_null($start) || is_null($end) || (float) $start === 0.0) {
            return null;
        }

        return round((($end - $start) / $start) * 100, 4);
    }

    private function calculateTotalReturnPercentage(?float $startPrice, ?float $endPrice, float $dividendsPaid): ?float
    {
        if (is_null($startPrice) || is_null($endPrice) || (float) $startPrice === 0.0) {
            return null;
        }

        return round((($endPrice - $startPrice + $dividendsPaid) / $startPrice) * 100, 4);
    }

    private function calculateNavErosionPercentage(?float $startNav, ?float $endNav, float $dividendsPaid): ?float
    {
        if (is_null($startNav) || is_null($endNav) || (float) $startNav === 0.0) {
            return null;
        }

        return round((($endNav - $startNav + $dividendsPaid) / $startNav) * 100, 4);
    }

    private function getNavDirectionId(?float $navErosionPercentage): ?int
    {
        if (is_null($navErosionPercentage)) {
            return null;
        }

        if ($navErosionPercentage > 0.25) {
            return MetricDirection::IMPROVING;
        }

        if ($navErosionPercentage < -0.25) {
            return MetricDirection::ERODING;
        }

        return MetricDirection::FLAT;
    }

    private function getAumDirectionId(?float $aumChangePercentage): ?int
    {
        if (is_null($aumChangePercentage)) {
            return null;
        }

        if ($aumChangePercentage > 0.25) {
            return MetricDirection::GROWING;
        }

        if ($aumChangePercentage < -0.25) {
            return MetricDirection::SHRINKING;
        }

        return MetricDirection::FLAT;
    }
}
