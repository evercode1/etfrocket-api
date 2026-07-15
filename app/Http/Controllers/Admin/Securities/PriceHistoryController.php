<?php

namespace App\Http\Controllers\Admin\Securities;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Models\SecurityPriceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceHistoryController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => [
                'required',
                'string',
                'max:20',
            ],
            'price_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'close_price' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.9999',
            ],
        ]);

        $symbol = strtoupper(trim($validated['symbol']));
        $priceDate = $validated['price_date'];
        $closePrice = round((float) $validated['close_price'], 4);

        $security = Security::query()
            ->where('symbol', $symbol)
            ->first();

        if (! $security) {
            return response()->json([
                'status' => 'error',
                'message' => "Security {$symbol} was not found.",
            ], 404);
        }

        $priceHistory = SecurityPriceHistory::query()
            ->where('security_id', $security->id)
            ->whereDate('price_date', $priceDate)
            ->first();

        if (! $priceHistory) {
            return response()->json([
                'status' => 'error',
                'message' => "No price history record was found for {$symbol} on {$priceDate}.",
            ], 404);
        }

        $previousClosePrice = (float) $priceHistory->close_price;

        if (round($previousClosePrice, 4) === $closePrice) {
            return response()->json([
                'status' => 'success',
                'message' => sprintf(
                    '%s already has a closing price of $%s for %s. No change was needed.',
                    $symbol,
                    number_format($closePrice, 4, '.', ''),
                    $priceDate,
                ),
                'data' => [
                    'security_id' => $security->id,
                    'symbol' => $symbol,
                    'price_date' => $priceDate,
                    'previous_close_price' => number_format(
                        $previousClosePrice,
                        4,
                        '.',
                        '',
                    ),
                    'close_price' => number_format(
                        $closePrice,
                        4,
                        '.',
                        '',
                    ),
                    'changed' => false,
                ],
            ]);
        }

        DB::transaction(function () use ($priceHistory, $closePrice) {
            $priceHistory->update([
                'close_price' => $closePrice,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => sprintf(
                '%s closing price for %s was updated from $%s to $%s.',
                $symbol,
                $priceDate,
                number_format($previousClosePrice, 4, '.', ''),
                number_format($closePrice, 4, '.', ''),
            ),
            'data' => [
                'security_id' => $security->id,
                'price_history_id' => $priceHistory->id,
                'symbol' => $symbol,
                'price_date' => $priceDate,
                'previous_close_price' => number_format(
                    $previousClosePrice,
                    4,
                    '.',
                    '',
                ),
                'close_price' => number_format(
                    $closePrice,
                    4,
                    '.',
                    '',
                ),
                'changed' => true,
            ],
        ]);
    }
}
