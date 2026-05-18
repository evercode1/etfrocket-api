<?php

namespace App\Services\Imports;

use App\Imports\EtfPriceHistoryImport;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportEtfPriceHistoryService
{
    public function import(int $etfId, string $filePath): array
    {
        $etf = Etf::findOrFail($etfId);

        $parsed = (new EtfPriceHistoryImport())->parse($filePath);

        $priceRows = collect($parsed['prices'])
            ->sortBy('price_date')
            ->values()
            ->toArray();

        $dividendRows = collect($parsed['dividends'])
            ->sortBy('ex_dividend_date')
            ->values()
            ->toArray();

        return DB::transaction(function () use ($etf, $priceRows, $dividendRows) {
            $deletedPriceRows = EtfPriceHistory::where('etf_id', $etf->id)
                ->delete();

            $deletedDividendRows = EtfDividendHistory::where('etf_id', $etf->id)
                ->delete();

            foreach ($priceRows as $row) {
                EtfPriceHistory::create([
                    'etf_id' => $etf->id,
                    'price_date' => $row['price_date'],
                    'close_price' => $row['close_price'],
                    'volume' => $row['volume'],
                    'data_source_id' => 1,
                    'retrieved_at' => now(),
                ]);
            }

            foreach ($dividendRows as $row) {
                $exDividendDate = Carbon::parse($row['ex_dividend_date']);

                EtfDividendHistory::create([
                    'etf_id' => $etf->id,
                    'dividend_amount' => $row['dividend_amount'],
                    'ex_dividend_date' => $exDividendDate->format('Y-m-d'),
                    'payment_date' => $exDividendDate->copy()->addDay()->format('Y-m-d'),
                    'data_source_id' => 1,
                    'retrieved_at' => now(),
                ]);
            }

            return [
                'etf_id' => $etf->id,
                'symbol' => $etf->symbol,

                'rows_imported' => count($priceRows),
                'rows_deleted' => $deletedPriceRows,

                'dividend_rows_imported' => count($dividendRows),
                'dividend_rows_deleted' => $deletedDividendRows,

                'start_date' => $priceRows[0]['price_date'] ?? null,
                'end_date' => $priceRows[count($priceRows) - 1]['price_date'] ?? null,
            ];
        });
    }
}
