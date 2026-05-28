<?php

namespace App\Services\Crons;

use App\Models\CronLog;
use App\Models\Interval;
use App\Models\NotificationStatus;
use App\Models\Status;
use App\Services\Crons\Notifications\CronFailNotificationService;
use Carbon\Carbon;

class CronService
{
    public static function runAndLogCron(

        string $command_signature,

        string $command_description,

        string $handler_class_name,

        string $handler_method_name,

        string $interval,

        array $payload = []

    ) {

        /*
        |--------------------------------------------------------------------------
        | Initial Values
        |--------------------------------------------------------------------------
        */

        $start_time = Carbon::now();

        $cron_fail_details = null;

        $success = null;

        $interval_id =
            Interval::getIntervalId(
                $interval
            );

        /*
        |--------------------------------------------------------------------------
        | Invoke Handler Dynamically
        |--------------------------------------------------------------------------
        */

        $class =
            '\\App\\Services\\Crons\\Handlers\\'.
            $handler_class_name;

        $handler =
            app($class);

        /*
        |--------------------------------------------------------------------------
        | Dynamically Call Handler Method
        |--------------------------------------------------------------------------
        */

        $handler_results =

            $handler->$handler_method_name(
                $payload
            );

        $cron_failure_details =
            $handler_results['cron_fail_details'];

        /*
        |--------------------------------------------------------------------------
        | Timing
        |--------------------------------------------------------------------------
        */

        $end_time = Carbon::now();

        $run_time =

            $start_time->diffInSeconds(

                $end_time

            );

        /*
        |--------------------------------------------------------------------------
        | Status Handling
        |--------------------------------------------------------------------------
        */

        $status_id =

            Status::setSuccessStatusId(

                $handler_results['success']

            );

        $notification_status_id =

            NotificationStatus::getNotificationStatusId(

                'nothing to send'

            );

        /*
        |--------------------------------------------------------------------------
        | Failure Handling
        |--------------------------------------------------------------------------
        */

        if (
            $handler_results['success'] == 0
        ) {

            $start_day =
                $start_time->format(
                    'Y-m-d'
                );

            $failed_status_id =

                Status::getStatusId(
                    'failed'
                );

            $previously_sent =

                NotificationStatus::getNotificationStatusId(
                    'previously sent'
                );

            $sent =

                NotificationStatus::getNotificationStatusId(
                    'sent'
                );

            if (

                CronLog::whereDate(
                    'created_at',
                    $start_day
                )
                    ->where(
                        'status_id',
                        $failed_status_id
                    )
                    ->where(
                        'notification_status_id',
                        $sent
                    )
                    ->exists()

            ) {

                $notification_status_id =
                    $previously_sent;
            } else {

                // CronFailNotificationService::sendNotifications(
                //     $command_signature,
                //     $cron_failure_details
                // );

                $notification_status_id =
                    $sent;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save Cron Log
        |--------------------------------------------------------------------------
        */

        CronLog::create([

            'cron_name' => $command_signature,

            'status_id' => $status_id,

            'cron_description' => $command_description,

            'cron_fail_details' => $cron_failure_details,

            'interval_id' => $interval_id,

            'run_time' => $run_time,

            'start_time' => $start_time,

            'end_time' => $end_time,

            'notification_status_id' => $notification_status_id,

        ]);
    }
}
