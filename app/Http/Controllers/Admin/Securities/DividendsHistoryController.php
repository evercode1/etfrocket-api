<?php

namespace App\Http\Controllers\Admin\Securities;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DividendsHistoryController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => [
                'required',
                'string',
                'max:20',
            ],
            'payment_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ex_dividend_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'dividend_amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999.999999',
            ],
        ]);

        $symbol = strtoupper(trim($validated['symbol']));
        $exDividendDate = $validated['ex_dividend_date'];
        $paymentDate = $validated['payment_date'];
        $dividendAmount = round(
            (float) $validated['dividend_amount'],
            4,
        );

        $security = Security::query()
            ->where('symbol', $symbol)
            ->first();

        if (! $security) {
            return response()->json([
                'status' => 'error',
                'message' => "Security {$symbol} was not found.",
            ], 404);
        }

        $existingDividend = SecurityDividendHistory::query()
            ->where('security_id', $security->id)
            ->whereDate('ex_dividend_date', $exDividendDate)
            ->first();

        if (

            $existingDividend &&

            round((float) $existingDividend->dividend_amount, 4) === $dividendAmount &&

            optional($existingDividend->payment_date)->format('Y-m-d') === $validated['payment_date']

        ) {
            return response()->json([
                'status' => 'success',
                'message' => sprintf(
                    '%s already has a dividend amount of $%s for %s. No change was needed.',
                    $symbol,
                    number_format($dividendAmount, 4, '.', ''),
                    $exDividendDate,
                ),
                'data' => [
                    'security_id' => $security->id,
                    'dividend_history_id' => $existingDividend->id,
                    'symbol' => $symbol,
                    'ex_dividend_date' => $exDividendDate,
                    'payment_date' => $paymentDate,
                    'previous_dividend_amount' => number_format(
                        (float) $existingDividend->dividend_amount,
                        4,
                        '.',
                        '',
                    ),
                    'dividend_amount' => number_format(
                        $dividendAmount,
                        4,
                        '.',
                        '',
                    ),
                    'created' => false,
                    'changed' => false,
                ],
            ]);
        }

        $previousDividendAmount = $existingDividend
            ? (float) $existingDividend->dividend_amount
            : null;

        $dividendHistory = DB::transaction(
            function () use (
                $existingDividend,
                $security,
                $exDividendDate,
                $dividendAmount,
                $paymentDate,
            ) {
                if ($existingDividend) {
                    $existingDividend->update([
                        'dividend_amount' => $dividendAmount,
                        'payment_date' => $paymentDate,
                    ]);

                    return $existingDividend;
                }

                return SecurityDividendHistory::query()->create([
                    'security_id' => $security->id,
                    'ex_dividend_date' => $exDividendDate,
                    'payment_date' => $paymentDate,
                    'dividend_amount' => $dividendAmount,
                ]);
            },
        );

        $wasCreated = $existingDividend === null;

        $message = $wasCreated
            ? sprintf(
                '%s dividend history for %s was added with an amount of $%s.',
                $symbol,
                $exDividendDate,
                number_format($dividendAmount, 4, '.', ''),
            )
            : sprintf(
                '%s dividend history for %s was updated from $%s to $%s.',
                $symbol,
                $exDividendDate,
                number_format($previousDividendAmount, 4, '.', ''),
                number_format($dividendAmount, 4, '.', ''),
            );

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'security_id' => $security->id,
                'dividend_history_id' => $dividendHistory->id,
                'symbol' => $symbol,
                'ex_dividend_date' => $exDividendDate,
                'previous_dividend_amount' => $previousDividendAmount === null
                    ? null
                    : number_format(
                        $previousDividendAmount,
                        4,
                        '.',
                        '',
                    ),
                'dividend_amount' => number_format(
                    $dividendAmount,
                    4,
                    '.',
                    '',
                ),
                'payment_date' => $paymentDate,
                'created' => $wasCreated,
                'changed' => true,
            ],
        ]);
    }
}
