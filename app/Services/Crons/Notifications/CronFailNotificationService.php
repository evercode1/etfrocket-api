<?php

namespace App\Services\Crons\Notifications;

use App\Mail\CronFailureNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class CronFailNotificationService
{
    public static function sendNotifications(string $cron_name, string $cron_failure_details)
    {

        // for dev, only send to user 1

        $user = User::where('email', 'ikon321@yahoo.com')->first();

        Mail::to($user)->send(new CronFailureNotification($cron_name, $cron_failure_details));

        // For production, uncomment the lines below

        // $users = User::where('is_admin', 1)->get();

        // foreach ( $users as $user ) {

        //     Mail::to($user)->send(new CronFailureNotification($cron_name, $cron_failure_details));

        // }

    }
}
