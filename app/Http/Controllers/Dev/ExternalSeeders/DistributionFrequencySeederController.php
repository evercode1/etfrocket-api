<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DistributionFrequency;
use Illuminate\Support\Facades\DB;

class DistributionFrequencySeederController extends Controller
{
    public function run(): void
    {
        DB::table('distribution_frequencies')->truncate();

        $frequencies = [

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

        foreach ($frequencies as $frequencyName) {

            DistributionFrequency::create([

                'distribution_frequency_name' => $frequencyName,

            ]);
        }
    }
}
