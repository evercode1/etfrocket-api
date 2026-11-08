<?php

namespace App\Queries\Admin\Securities;

use App\Models\Security;
use App\Models\Status;
use App\Services\Aum\FundDataCoverage;
use Illuminate\Http\Request;

class ListSecuritiesDataQuery
{
    public function getData(Request $request): array
    {
        $query =
            Security::query()
                ->select([
                    'securities.id',
                    'securities.symbol',
                    'securities.security_type_id',
                    'securities.status_id',
                    'security_details.security_name',
                    'security_details.etf_issuer_id',
                    'security_details.etf_strategy_type_id',
                    'security_details.distribution_frequency_id',
                    'security_details.expense_ratio',
                    'security_details.website_url',
                ])
                ->leftJoin(
                    'security_details',
                    'securities.id',
                    '=',
                    'security_details.security_id'
                )
                ->with([
                    'securityType:id,security_type_name',
                    'status:id,status_name',
                    'detail.issuer:id,etf_issuer_name',
                    'detail.strategyType:id,etf_strategy_type_name',
                    'detail.distributionFrequency:id,distribution_frequency_name',
                ])
                ->withCount(
                    'updateSchedules'
                );

        if ($request->filled('search')) {
            $search =
                $request->input('search');

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'securities.symbol',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'security_details.security_name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->filled('status_id')) {
            $query->where(
                'securities.status_id',
                $request->input('status_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fund data coverage (shared with Pipeline Health)
        |--------------------------------------------------------------------------
        |
        | coverage filter: current | stale | pending | not_covered | failing
        | (failing = the latest scrape attempt failed).
        |
        */

        $latestDates = FundDataCoverage::latestAumDates();

        $coveredIds = FundDataCoverage::coveredSecurities()
            ->pluck('securities.id')
            ->flip();

        $attempts = FundDataCoverage::latestScrapeAttempts(
            Security::query()->pluck('id')->all()
        );

        $coverageOf = fn (int $id) => FundDataCoverage::status(
            isset($coveredIds[$id]),
            $latestDates[$id] ?? null
        );

        $failingOf = fn (int $id) => FundDataCoverage::latestAttemptFailed(
            $attempts[$id] ?? []
        );

        if ($request->filled('coverage')) {

            $coverage = $request->input('coverage');

            $matchingIds = collect(array_keys($attempts))
                ->filter(fn (int $id) => $coverage === 'failing'
                    ? $failingOf($id)
                    : $coverageOf($id) === $coverage)
                ->values()
                ->all();

            $query->whereIn('securities.id', $matchingIds ?: [0]);
        }

        $paginator =
            $query
                ->orderBy(
                    'securities.symbol'
                )
                ->paginate(
                    $request->input(
                        'per_page',
                        25
                    )
                )
                ->through(function (Security $security) use ($latestDates, $attempts, $coverageOf, $failingOf) {
                    return [
                        'id' => $security->id,

                        'symbol' => $security->symbol,

                        'security_type' => $security
                            ->securityType
                            ?->security_type_name,

                        'status' => $security
                            ->status
                            ?->status_name,

                        'security_name' => $security
                            ->detail
                            ?->security_name,

                        'issuer' => $security
                            ->detail
                            ?->issuer
                            ?->etf_issuer_name,

                        'strategy' => $security
                            ->detail
                            ?->strategyType
                            ?->etf_strategy_type_name,

                        'distribution_frequency' => $security
                            ->detail
                            ?->distributionFrequency
                            ?->distribution_frequency_name,

                        'schedule_count' => $security
                            ->update_schedules_count,
                        'coverage_status' => $coverageOf($security->id),
                        'latest_aum_date' => $latestDates[$security->id] ?? null,
                        'has_scraper' => FundDataCoverage::hasScraper($security),
                        'last_scrape' => [
                            'failed' => $failingOf($security->id),
                            'last_success_at' => $attempts[$security->id]['last_success_at'] ?? null,
                            'last_failure_at' => $attempts[$security->id]['last_failure_at'] ?? null,
                            'last_error' => $failingOf($security->id)
                                ? ($attempts[$security->id]['last_error'] ?? null)
                                : null,
                        ],
                    ];
                });

        return [
            'data' => $paginator,

            'meta' => [
                'total_active' => Security::where(
                    'status_id',
                    Status::ACTIVE
                )->count(),

                'total_retired' => Security::where(
                    'status_id',
                    Status::RETIRED
                )->count(),
            ],
        ];
    }
}
