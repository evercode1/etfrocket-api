<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\NotificationStatus;

class NotificationStatusesSeederController extends Controller
{
    public function run(): void
    {
        NotificationStatus::truncate();

        $statuses = [

            'sent',
            'previously sent',
            'nothing to send',

        ];

        foreach ($statuses as $statusName) {

            NotificationStatus::create([

                'notification_status_name' => $statusName,

            ]);
        }
    }
}
