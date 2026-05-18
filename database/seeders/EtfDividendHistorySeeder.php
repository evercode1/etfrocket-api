<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use Illuminate\Database\Seeder;

// NOTE:
// This seeder uses synthetic historical data for local development,
// testing, and UI prototyping. It should not be treated as accurate
// market data.

class EtfDividendHistorySeeder extends Seeder
{
    public function run(): void
    {
        $dataSourceId = DataSource::MANUAL_ENTRY;

        EtfDividendHistory::truncate();

        $rows = [
            'CHPY' => [
                ['amount' => 0.4089, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.4215, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.3978, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.4452, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.6041, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],

            'AMDY' => [
                ['amount' => 0.3288, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.4124, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.3766, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.5027, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.9448, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],

            'GOOY' => [
                ['amount' => 0.0818, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.0964, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.1052, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.1181, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.2247, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],

            'QQQI' => [

                ['amount' => 0.6297, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-24'],

            ],

            'NVII' => [
                ['amount' => 0.1817, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.1664, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.1722, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.1888, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.1795, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],

            'BLOX' => [
                ['amount' => 0.0977, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.1011, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.1038, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.1054, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.1073, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],

            'LFGY' => [
                ['amount' => 0.2033, 'ex_date' => '2026-04-01', 'payment_date' => '2026-04-02'],
                ['amount' => 0.2116, 'ex_date' => '2026-04-08', 'payment_date' => '2026-04-09'],
                ['amount' => 0.2184, 'ex_date' => '2026-04-15', 'payment_date' => '2026-04-16'],
                ['amount' => 0.2201, 'ex_date' => '2026-04-22', 'payment_date' => '2026-04-23'],
                ['amount' => 0.2228, 'ex_date' => '2026-05-01', 'payment_date' => '2026-05-04'],
            ],
        ];

        foreach ($rows as $symbol => $dividends) {
            $etf = Etf::where('symbol', $symbol)->first();

            if (! $etf) {
                continue;
            }

            foreach ($dividends as $dividend) {
                EtfDividendHistory::updateOrCreate(
                    [
                        'etf_id' => $etf->id,
                        'ex_dividend_date' => $dividend['ex_date'],
                    ],
                    [
                        'dividend_amount' => $dividend['amount'],
                        'payment_date' => $dividend['payment_date'],
                        'data_source_id' => $dataSourceId,
                        'retrieved_at' => now(),
                    ]
                );
            }
        }
    }
}
