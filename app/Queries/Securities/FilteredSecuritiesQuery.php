<?php

namespace App\Queries\Securities;

use App\Models\Security;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FilteredSecuritiesQuery
{
    public function getData(array $resolvedFilters, ?int $userId = null): LengthAwarePaginator
    {
        $column = $resolvedFilters['column'];
        $sortDirection = $resolvedFilters['sort_direction'];
        $scope = $resolvedFilters['scope'];
        $days = $resolvedFilters['days'];
        $perPage = $resolvedFilters['per_page'] ?? $resolvedFilters['limit'] ?? 25;

        $query = Security::query()

            ->select([

                'securities.id',
                'securities.symbol',
                'security_details.security_name',
                'securities.website_url',

                'security_metrics.performance_range_type_id',

                'security_metrics.start_date',
                'security_metrics.end_date',

                'security_metrics.start_price',
                'security_metrics.end_price',
                'security_metrics.price_change',
                'security_metrics.price_change_percentage',

                'security_metrics.dividends_paid',
                'security_metrics.dividend_count',
                'security_metrics.average_dividend',

                'security_metrics.total_return_percentage',

                'security_metrics.start_nav',
                'security_metrics.end_nav',
                'security_metrics.nav_change',
                'security_metrics.nav_erosion_percentage',
                'security_metrics.nav_direction_id',

                'security_metrics.start_aum',
                'security_metrics.end_aum',
                'security_metrics.aum_change',
                'security_metrics.aum_change_percentage',
                'security_metrics.aum_direction_id',

                'security_metrics.calculated_at',

            ])

            ->leftJoin('security_metrics', 'securities.id', '=', 'security_metrics.security_id')

            ->leftJoin('security_details', 'securities.id', '=', 'security_details.security_id');

        $this->applyRange($query, $days);

        $this->applyScope($query, $scope, $userId);

        $query->whereNotNull("security_metrics.{$column}");

        return $query
            ->orderBy("security_metrics.{$column}", $sortDirection)
            ->paginate($perPage);
    }

    private function applyRange(Builder $query, ?int $days): void
    {
        if (! $days) {
            return;
        }

        $fromDate = Carbon::now()->subDays($days)->toDateString();

        $query->whereDate('security_metrics.calculated_at', '>=', $fromDate);
    }

    private function applyScope(Builder $query, string $scope, ?int $userId): void
    {
        if ($scope !== 'owned') {
            return;
        }

        if (! $userId) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->join('portfolio_holdings', function ($join) use ($userId) {
            $join->on('securities.id', '=', 'portfolio_holdings.security_id')
                ->where('portfolio_holdings.user_id', '=', $userId)
                ->where('portfolio_holdings.is_active', '=', true);
        });
    }
}
