<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// NOTE:
// This seeder uses synthetic historical data for local development,
// testing, and UI prototyping. It should not be treated as accurate
// market data.

class EtfAumHistorySeeder extends Seeder
{
    public function run(): void
    {
        EtfAumHistory::truncate();

        $dataSourceId = DataSource::MANUAL_ENTRY;

        $rows = [
            'CHPY' => [
                'start' => 590_000_000,
                'end' => 620_000_000,
            ],
            'AMDY' => [
                'start' => 230_000_000,
                'end' => 235_000_000,
            ],
            'GOOY' => [
                'start' => 182_000_000,
                'end' => 185_000_000,
            ],
            'QQQI' => [
                'start' => 925_000_000,
                'end' => 950_000_000,
            ],
            'NVII' => [
                'start' => 72_000_000,
                'end' => 75_000_000,
            ],
            'BLOX' => [
                'start' => 53_000_000,
                'end' => 55_000_000,
            ],
            'LFGY' => [
                'start' => 139_000_000,
                'end' => 140_000_000,
            ],
        ];

        foreach ($rows as $symbol => $aumRange) {
            $etf = Etf::where('symbol', $symbol)->first();

            if (! $etf) {
                continue;
            }

            $startDate = Carbon::parse('2026-04-01');
            $endDate = Carbon::parse('2026-05-01');
            $totalDays = $startDate->diffInDays($endDate);

            for ($day = 0; $day <= $totalDays; $day++) {
                $aumDate = $startDate->copy()->addDays($day)->toDateString();

                $assetsUnderManagement = round(
                    $aumRange['start'] +
                        (($aumRange['end'] - $aumRange['start']) / $totalDays) * $day
                );

                EtfAumHistory::updateOrCreate(
                    [
                        'etf_id' => $etf->id,
                        'aum_date' => $aumDate,
                    ],
                    [
                        'assets_under_management' => $assetsUnderManagement,
                        'data_source_id' => $dataSourceId,
                        'retrieved_at' => now(),
                    ]
                );
            }
        }
    }
}
