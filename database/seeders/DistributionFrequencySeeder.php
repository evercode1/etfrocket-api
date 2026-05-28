<?php

namespace Database\Seeders;

use App\Models\DistributionFrequency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistributionFrequencySeeder extends Seeder
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

        foreach ($frequencies as $frequency_name) {

            DistributionFrequency::create([

                'distribution_frequency_name' => $frequency_name,

            ]);
        }
    }
}
