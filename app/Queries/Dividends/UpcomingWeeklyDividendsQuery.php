<?php

namespace App\Queries\Dividends;

use App\Models\EtfDividendHistory;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Carbon\Carbon;

class UpcomingWeeklyDividendsQuery
{
    private const WEEKLY = 2;

    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService
    ) {}

    public function getData(int $portfolioId): array
    {
        $weeklyHoldings = $this->holdingsStatsService
            ->getCurrentHoldings($portfolioId)
            ->filter(function (array $holding) {
                return (int) $holding['distribution_frequency_id'] === self::WEEKLY;
            })
            ->values();

        if ($weeklyHoldings->isEmpty()) {
            return [];
        }

        return $weeklyHoldings
            ->map(function (array $holding) {
                return $this->buildDividendEvent($holding);
            })
            ->filter()
            ->sortBy(function (array $event) {
                return $event['ex_dividend_date'] ?? '9999-12-31';
            })
            ->values()
            ->toArray();
    }

    private function buildDividendEvent(array $holding): ?array
    {
        $today = Carbon::today();

        $declaredDividend = EtfDividendHistory::where('etf_id', $holding['etf_id'])
            ->whereDate('ex_dividend_date', '>=', $today->toDateString())
            ->orderBy('ex_dividend_date')
            ->first();

        if ($declaredDividend) {
            return [
                'etf_id' => (int) $holding['etf_id'],
                'symbol' => $holding['symbol'],
                'fund_name' => $holding['fund_name'],
                'shares' => round((float) $holding['shares'], 4),
                'distribution_amount' => round((float) $declaredDividend->dividend_amount, 4),
                'estimated_payment_amount' => round(
                    (float) $holding['shares'] * (float) $declaredDividend->dividend_amount,
                    4
                ),
                'ex_dividend_date' => $declaredDividend->ex_dividend_date
                    ? Carbon::parse($declaredDividend->ex_dividend_date)->toDateString()
                    : null,
                'payment_date' => $declaredDividend->payment_date
                    ? Carbon::parse($declaredDividend->payment_date)->toDateString()
                    : null,
                'status' => 'Declared',
                'note' => 'Official dividend has been declared.',
            ];
        }

        $latestDividend = EtfDividendHistory::where('etf_id', $holding['etf_id'])
            ->orderByDesc('ex_dividend_date')
            ->first();

        if (! $latestDividend) {
            return [
                'etf_id' => (int) $holding['etf_id'],
                'symbol' => $holding['symbol'],
                'fund_name' => $holding['fund_name'],
                'shares' => round((float) $holding['shares'], 4),
                'distribution_amount' => null,
                'estimated_payment_amount' => null,
                'ex_dividend_date' => null,
                'payment_date' => null,
                'status' => 'Unknown',
                'note' => 'No dividend history is available yet.',
            ];
        }

        $nextExpectedExDate = Carbon::parse($latestDividend->ex_dividend_date)
            ->addDays(7);

        while ($nextExpectedExDate->lt($today)) {
            $nextExpectedExDate->addDays(7);
        }

        return [
            'etf_id' => (int) $holding['etf_id'],
            'symbol' => $holding['symbol'],
            'fund_name' => $holding['fund_name'],
            'shares' => round((float) $holding['shares'], 4),
            'distribution_amount' => null,
            'estimated_payment_amount' => null,
            'ex_dividend_date' => $nextExpectedExDate->toDateString(),
            'payment_date' => null,
            'status' => 'Expected',
            'note' => 'Estimated from weekly payout cadence. Amount remains TBD until declared.',
        ];
    }
}
