<?php

namespace Database\Seeders;

use App\Models\PerformanceRangeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceRangeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('performance_range_types')->truncate();

        $ranges = [

            '5 Day',
            '30 Day',
            '90 Day',
            'Year To Date',
            '1 Year',
            'Max',

        ];

        foreach ($ranges as $rangeName) {

            PerformanceRangeType::create([

                'performance_range_type_name' => $rangeName,

            ]);
        }
    }
}
