<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\Status;

class StatusesSeederController extends Controller
{
    public function run(): void
    {
        Status::truncate();

        $statuses = [

            'Dumped',
            'Pending Email Verification',
            'Pending',
            'Active',
            'Rejected',
            'Whitelist',
            'Submitted',
            'Under Review',
            'Approved',
            'Open',
            'Closed',
            'Running',
            'Completed',
            'Inactive',
            'Success',
            'Failed',
            'Processing',
            'Retired',

        ];

        foreach ($statuses as $status_name) {

            Status::create([

                'status_name' => $status_name,

            ]);
        }
    }
}
