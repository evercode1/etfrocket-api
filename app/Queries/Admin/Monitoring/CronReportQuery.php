<?php

namespace App\Queries\Admin\Monitoring;

use App\Models\CronLog;
use App\Models\Status;

class CronReportQuery
{
    public function getData(): array
    {
        $successfulStatusId =
            Status::getStatusId(
                'completed'
            );

        $failedStatusId =
            Status::getStatusId(
                'failed'
            );

        $summary = [

            'successful_runs' =>

            CronLog::where(
                'status_id',
                $successfulStatusId
            )->count(),

            'failed_runs' =>

            CronLog::where(
                'status_id',
                $failedStatusId
            )->count(),

            'average_runtime' =>

            round(

                CronLog::avg(
                    'run_time'
                ) ?? 0

            ),

            'active_crons' =>

            CronLog::distinct(
                'cron_name'
            )->count(),

        ];

        $logs =

            CronLog::select([

                'cron_logs.id',

                'cron_logs.cron_name',

                'cron_logs.cron_description',

                'cron_logs.cron_fail_details',

                'cron_logs.run_time',

                'cron_logs.start_time',

                'cron_logs.end_time',

                'statuses.status_name',

                'intervals.interval_name',

                'notification_statuses.notification_status_name',

            ])

            ->leftJoin(

                'statuses',

                'cron_logs.status_id',

                '=',

                'statuses.id'

            )

            ->leftJoin(

                'intervals',

                'cron_logs.interval_id',

                '=',

                'intervals.id'

            )

            ->leftJoin(

                'notification_statuses',

                'cron_logs.notification_status_id',

                '=',

                'notification_statuses.id'

            )

            ->orderByDesc(
                'cron_logs.start_time'
            )

            ->paginate(25);

        return [

            'summary' =>

            $summary,

            'logs' =>

            $logs,

        ];
    }
}
