<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Models\Interval;
use App\Http\Controllers\Controller;

class IntervalsSeederController extends Controller
{
    public function run(): void
    {
        Interval::truncate();

        $intervals = [

            'Daily',
            'Weekly',
            'Bi-Weekly',
            'Monthly',
            'Quarterly',
            'Semi-Annual',
            'Annual',
            'Variable',
            'None',

        ];

        foreach ($intervals as $intervalName) {

            Interval::create([

                'interval_name' => $intervalName,

            ]);
        }
    }
}
