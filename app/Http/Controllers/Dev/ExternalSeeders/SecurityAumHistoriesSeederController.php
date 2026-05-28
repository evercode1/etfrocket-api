<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityNavHistory;
use Carbon\Carbon;

class SecurityAumHistoriesSeederController extends Controller
{
    public function run(): void
    {
        SecurityAumHistory::truncate();

        SecurityNavHistory::truncate();

        $securities = Security::whereIn('id', [1, 2, 3, 4])

            ->orderBy('id')

            ->get();

        foreach ($securities as $security) {

            $this->seedSecurityHistory($security);
        }
    }

    private function seedSecurityHistory(
        Security $security
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Security Specific Baselines
        |--------------------------------------------------------------------------
        */

        $config = match ((int) $security->id) {

            1 => [

                'nav' => 24,

                'aum' => 850000000,

            ],

            2 => [

                'nav' => 26,

                'aum' => 120000000,

            ],

            3 => [

                'nav' => 28,

                'aum' => 95000000,

            ],

            4 => [

                'nav' => 22,

                'aum' => 275000000,

            ],

            default => [

                'nav' => 25,

                'aum' => 100000000,

            ],
        };

        $currentNav =
            $config['nav'];

        $currentAum =
            $config['aum'];

        $startDate =
            Carbon::create(
                2025,
                5,
                28
            );

        $endDate =
            now();

        while (
            $startDate->lte($endDate)
        ) {

            /*
            |--------------------------------------------------------------------------
            | Random Daily Drift
            |--------------------------------------------------------------------------
            */

            $currentNav += round(

                mt_rand(-6500, 6500) / 10000,

                4

            );

            $currentNav =
                max(
                    $currentNav,
                    5
                );

            $currentAum += mt_rand(

                -5000000,

                7000000

            );

            $currentAum =
                max(
                    $currentAum,
                    1000000
                );

            /*
            |--------------------------------------------------------------------------
            | Save NAV
            |--------------------------------------------------------------------------
            */

            SecurityNavHistory::create([

                'security_id' => $security->id,

                'nav_date' => $startDate->format(
                    'Y-m-d'
                ),

                'nav_per_share' => round(
                    $currentNav,
                    4
                ),

                'data_source_id' => DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Save AUM
            |--------------------------------------------------------------------------
            */

            SecurityAumHistory::create([

                'security_id' => $security->id,

                'aum_date' => $startDate->format(
                    'Y-m-d'
                ),

                'assets_under_management' => (int) round(
                    $currentAum
                ),

                'data_source_id' => DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

            $startDate->addDay();
        }
    }
}
