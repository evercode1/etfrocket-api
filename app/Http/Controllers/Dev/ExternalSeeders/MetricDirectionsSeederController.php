<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\MetricDirection;
use Illuminate\Support\Facades\DB;

class MetricDirectionsSeederController extends Controller
{
    public function run(): void
    {
        DB::table('metric_directions')->truncate();

        $directions = [

            'Improving',
            'Eroding',
            'Flat',
            'Growing',
            'Shrinking',

        ];

        foreach ($directions as $directionName) {

            MetricDirection::create([

                'metric_direction_name' => $directionName,

            ]);
        }
    }
}
