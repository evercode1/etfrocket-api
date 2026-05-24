<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationStatus;

class NotificationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NotificationStatus::truncate();

        $values = [

            'sent',

            'previously sent',

            'nothing to send'
        ];

        foreach ($values as $value) {

            NotificationStatus::create([

                'notification_status_name' => $value,

            ]);
        }
    }
}
