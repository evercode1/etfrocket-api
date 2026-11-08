<?php

namespace App\Services\Crons\Handlers;

use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use App\Services\Crons\FundDataBatchDispatcher;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunScheduledUpdatesHandler
{
    public function handleRunScheduledUpdates(
        array $payload = []
    ): array {

        try {

            Log::info(
                'SCHEDULED UPDATE HANDLER STARTED'
            );

            /*
            |--------------------------------------------------------------------------
            | Fund data only, every day
            |--------------------------------------------------------------------------
            |
            | Only FUND_DATA schedules (free issuer scrapers: AUM + NAV) run,
            | and only for issuers that have a scraper.
            | DIVIDEND schedules depend on the paid Twelve Data API and are
            | intentionally skipped; their rows are left untouched.
            |
            | Fund data runs daily at each schedule's run_hour. run_day is
            | ignored (it is stored as 1-7 while Carbon's dayOfWeek is 0-6,
            | so day-7 schedules previously never ran). Re-runs on the same
            | data date are idempotent (updateOrCreate on security + date).
            |
            */

            $runHour =
                $payload['hour']
                ?? now()->hour;

            $limit =
                $payload['limit']
                ?? null;

            $securityId =
                $payload['security_id']
                ?? null;

            $query =

                SecurityUpdateSchedule::query()

                    ->where(
                        'status_id',
                        Status::ACTIVE
                    )

                    ->where(
                        'security_update_type_id',
                        SecurityUpdateType::FUND_DATA
                    )

                    // Skip securities whose issuer has no scraper; they would
                    // fail every run. Their schedule rows are left untouched.
                    ->whereIn(
                        'security_id',
                        SecurityDetail::query()
                            ->select('security_id')
                            ->whereIn(
                                'etf_issuer_id',
                                AiSecurityFundDataExtractionService::supportedIssuerIds()
                            )
                    )

                    ->where(
                        'run_hour',
                        $runHour
                    );

            if ($securityId) {

                $query->where(
                    'security_id',
                    $securityId
                );
            }

            if ($limit) {

                $query->limit(
                    (int) $limit
                );
            }

            $schedules =

                $query

                    ->orderBy(
                        'security_id'
                    )

                    ->get();

            Log::info(

                'SCHEDULE QUERY COMPLETE',

                [

                    'run_hour' => $runHour,

                    'count' => $schedules->count(),

                ]

            );

            if (

                $schedules->isEmpty()

            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            app(FundDataBatchDispatcher::class)->dispatch(

                $schedules,

                'Scheduled fund data updates queued. '.

                    "Run hour {$runHour}. ".

                    "Total updates: {$schedules->count()}"

            );

            Log::info(
                'SCHEDULED UPDATE HANDLER COMPLETE'
            );

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];

        } catch (Throwable $e) {

            Log::error(

                'SCHEDULED UPDATE HANDLER FAILED',

                [

                    'message' => $e->getMessage(),

                    'trace' => $e->getTraceAsString(),

                ]

            );

            report($e);

            return [

                'success' => 0,

                'cron_fail_details' => $this->errorMessage().
                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {
        return 'Scheduled update processing failed. ';
    }
}
