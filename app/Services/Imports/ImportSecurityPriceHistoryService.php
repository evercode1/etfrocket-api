<?php

namespace App\Services\Imports;

use App\Imports\SecurityPriceHistoryImport;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportSecurityPriceHistoryService
{
    public function import(int $securityId, string $filePath): array
    {
        $security = Security::findOrFail($securityId);

        $parsed = (new SecurityPriceHistoryImport)->parse($filePath);

        $priceRows = collect($parsed['prices'])
            ->sortBy('price_date')
            ->values()
            ->toArray();

        $dividendRows = collect($parsed['dividends'])
            ->sortBy('ex_dividend_date')
            ->values()
            ->toArray();

        return DB::transaction(function () use ($security, $priceRows, $dividendRows) {
            $deletedPriceRows = SecurityPriceHistory::where('security_id', $security->id)
                ->delete();

            $deletedDividendRows = SecurityDividendHistory::where('security_id', $security->id)
                ->delete();

            foreach ($priceRows as $row) {
                SecurityPriceHistory::create([
                    'security_id' => $security->id,
                    'price_date' => $row['price_date'],
                    'close_price' => $row['close_price'],
                    'volume' => $row['volume'],
                    'data_source_id' => 1,
                    'retrieved_at' => now(),
                ]);
            }

            foreach ($dividendRows as $row) {
                $exDividendDate = Carbon::parse($row['ex_dividend_date']);

                SecurityDividendHistory::create([
                    'security_id' => $security->id,
                    'dividend_amount' => $row['dividend_amount'],
                    'ex_dividend_date' => $exDividendDate->format('Y-m-d'),
                    'payment_date' => $exDividendDate->copy()->addDay()->format('Y-m-d'),
                    'data_source_id' => 1,
                    'retrieved_at' => now(),
                ]);
            }

            return [
                'security_id' => $security->id,
                'symbol' => $security->symbol,

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
