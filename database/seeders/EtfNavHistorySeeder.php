<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfNavHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// NOTE:
// This seeder uses synthetic historical data for local development,
// testing, and UI prototyping. It should not be treated as accurate
// market data.

class EtfNavHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('etf_nav_histories')->truncate();

        $dataSourceId = DataSource::MANUAL_ENTRY;

        $rows = [
            'CHPY' => [
                'start' => 47.9400,
                'end' => 47.6200,
            ],
            'AMDY' => [
                'start' => 16.2400,
                'end' => 15.8200,
            ],
            'GOOY' => [
                'start' => 17.6200,
                'end' => 17.4800,
            ],
            'QQQI' => [
                'start' => 61.8200,
                'end' => 62.9400,
            ],
            'NVII' => [
                'start' => 30.4200,
                'end' => 31.1200,
            ],
            'BLOX' => [
                'start' => 30.1800,
                'end' => 30.7400,
            ],
            'LFGY' => [
                'start' => 45.7800,
                'end' => 45.3400,
            ],
        ];

        foreach ($rows as $symbol => $navRange) {
            $etf = Etf::where('symbol', $symbol)->first();

            if (! $etf) {
                continue;
            }

            $startDate = Carbon::parse('2026-04-01');
            $endDate = Carbon::parse('2026-05-01');
            $totalDays = $startDate->diffInDays($endDate);

            for ($day = 0; $day <= $totalDays; $day++) {
                $navDate = $startDate->copy()->addDays($day)->toDateString();

                $navPerShare = round(
                    $navRange['start'] +
                        (($navRange['end'] - $navRange['start']) / $totalDays) * $day,
                    4
                );

                EtfNavHistory::updateOrCreate(
                    [
                        'etf_id' => $etf->id,
                        'nav_date' => $navDate,
                    ],
                    [
                        'nav_per_share' => $navPerShare,
                        'data_source_id' => $dataSourceId,
                        'retrieved_at' => now(),
                    ]
                );
            }
        }
    }
}
