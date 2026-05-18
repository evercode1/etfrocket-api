<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfNavHistory;
use Carbon\Carbon;

class EtfAumHistoriesSeederController extends Controller
{
    public function run(): void
    {
        EtfAumHistory::truncate();

        EtfNavHistory::truncate();

        $etfs = Etf::whereIn('id', [1, 2, 3, 4])
            ->orderBy('id')
            ->get();

        foreach ($etfs as $etf) {
            $this->seedEtfHistory($etf);
        }
    }

    private function seedEtfHistory(Etf $etf): void
    {
        /*
        |--------------------------------------------------------------------------
        | ETF Specific Baselines
        |--------------------------------------------------------------------------
        */

        $config = match ((int) $etf->id) {

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

        $currentNav = $config['nav'];

        $currentAum = $config['aum'];

        $startDate = Carbon::create(2025, 5, 28);

        $endDate = now();

        while ($startDate->lte($endDate)) {

            /*
            |--------------------------------------------------------------------------
            | Random Daily Drift
            |--------------------------------------------------------------------------
            */

            $currentNav += fake()->randomFloat(4, -0.65, 0.65);

            $currentNav = max($currentNav, 5);

            $currentAum += fake()->numberBetween(-5000000, 7000000);

            $currentAum = max($currentAum, 1000000);

            /*
            |--------------------------------------------------------------------------
            | Save NAV
            |--------------------------------------------------------------------------
            */

            EtfNavHistory::create([

                'etf_id' => $etf->id,

                'nav_date' => $startDate->format('Y-m-d'),

                'nav_per_share' => round($currentNav, 4),

                'data_source_id' => DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Save AUM
            |--------------------------------------------------------------------------
            */

            EtfAumHistory::create([

                'etf_id' => $etf->id,

                'aum_date' => $startDate->format('Y-m-d'),

                'assets_under_management' => (int) round($currentAum),

                'data_source_id' => DataSource::MANUAL_ENTRY,

                'retrieved_at' => now(),

            ]);

            $startDate->addDay();
        }
    }
}
