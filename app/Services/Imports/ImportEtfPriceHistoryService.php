<?php

namespace App\Services\Imports;

use App\Imports\EtfPriceHistoryImport;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use Illuminate\Support\Facades\DB;

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
                ]);
            }

            foreach ($dividendRows as $row) {
                EtfDividendHistory::create([
                    'etf_id' => $etf->id,
                    'dividend_amount' => $row['dividend_amount'],
                    'ex_dividend_date' => $row['ex_dividend_date'],
                    'payment_date' => null,
                    'data_source_id' => 1,
                    'source_as_of_date' => null,
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
