<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
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
