<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityPriceHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// NOTE:
// This seeder uses synthetic historical data for local development,
// testing, and UI prototyping. It should not be treated as accurate
// market data.

class SecurityPriceHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('security_price_histories')->truncate();

        $dataSourceId = DataSource::MANUAL_ENTRY;

        $rows = [
            'CHPY' => ['start' => 48.06, 'end' => 47.62, 'start_volume' => 319_000, 'end_volume' => 334_000],
            'AMDY' => ['start' => 16.38, 'end' => 15.84, 'start_volume' => 3_020_000, 'end_volume' => 3_160_000],
            'GOOY' => ['start' => 17.76, 'end' => 17.58, 'start_volume' => 561_000, 'end_volume' => 577_000],
            'QQQI' => ['start' => 63.44, 'end' => 64.62, 'start_volume' => 1_790_000, 'end_volume' => 1_860_000],
            'NVII' => ['start' => 32.28, 'end' => 33.04, 'start_volume' => 244_000, 'end_volume' => 258_000],
            'BLOX' => ['start' => 31.72, 'end' => 32.46, 'start_volume' => 166_000, 'end_volume' => 174_000],
            'LFGY' => ['start' => 45.62, 'end' => 45.18, 'start_volume' => 229_000, 'end_volume' => 237_000],
        ];

        foreach ($rows as $symbol => $range) {
            $security = Security::where('symbol', $symbol)->first();

            if (! $security) {
                continue;
            }

            $startDate = Carbon::parse('2026-04-01');
            $endDate = Carbon::parse('2026-05-01');
            $totalDays = $startDate->diffInDays($endDate);

            for ($day = 0; $day <= $totalDays; $day++) {
                $priceDate = $startDate->copy()->addDays($day)->toDateString();

                $closePrice = round(
                    $range['start'] + (($range['end'] - $range['start']) / $totalDays) * $day,
                    4
                );

                $volume = round(
                    $range['start_volume'] + (($range['end_volume'] - $range['start_volume']) / $totalDays) * $day
                );

                SecurityPriceHistory::updateOrCreate(
                    [
                        'security_id' => $security->id,
                        'price_date' => $priceDate,
                    ],
                    [
                        'close_price' => $closePrice,
                        'volume' => $volume,
                        'data_source_id' => $dataSourceId,
                        'retrieved_at' => now(),
                    ]
                );
            }
        }
    }
}
