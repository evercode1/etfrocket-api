<?php

namespace App\Http\Controllers\User\Portfolios;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Services\PortfolioTransactions\CreatePortfolioTransactionService;
use App\Services\PortfolioTransactions\ExportPortfolioTransactionsService;
use App\Services\PortfolioTransactions\ImportPortfolioTransactionsService;
use App\Services\PortfolioTransactions\ListPortfolioTransactionsService;
use App\Services\PortfolioTransactions\UpdatePortfolioTransactionService;
use App\Services\PortfolioTransactions\UserBulkTransactionUploadService;
use App\Services\PortfolioTransactions\ViewPortfolioTransactionFormService;
use App\Utilities\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortfolioTransactionsController extends Controller
{
    public function listPortfolioTransactions(
        Request $request,
        int $portfolio_id,
        ListPortfolioTransactionsService $service
    ) {

        $securityId = $request->filled('security_id')

            ? (int) $request->input('security_id')

            : null;

        try {

            $transactions = $service->getData(

                $request,

                Auth::id(),

                $portfolio_id,

                $securityId

            );
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ], 200);
    }

    public function getCreatePortfolioTransactionFormConfig(int $portfolio_id)
    {
        try {

            Portfolio::where('user_id', Auth::id())
                ->where('id', $portfolio_id)
                ->firstOrFail();
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'portfolio_id' => $portfolio_id,
                'fields' => $this->transactionFormFields(),
            ],
        ], 200);
    }

    public function createPortfolioTransaction(
        Request $request,
        int $portfolio_id,
        CreatePortfolioTransactionService $service
    ) {
        $request->validate([
            'security_id' => ['required', 'integer'],
            'transaction_type_id' => ['required', 'integer'],
            'shares' => ['required', 'numeric', 'gt:0'],
            'price_per_share' => ['required', 'numeric', 'gte:0'],
            'transaction_date' => ['required', 'date'],
        ]);

        try {

            $transaction = $service->create(
                Auth::id(),
                $portfolio_id,
                $request->all()
            );
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 201);
    }

    public function getUpdatePortfolioTransactionFormConfig(
        int $id,
        ViewPortfolioTransactionFormService $service
    ) {
        try {

            $transaction = $service->getData(Auth::id(), $id);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'portfolio_transaction_id' => $transaction->id,
                'portfolio_id' => $transaction->portfolio_id,
                'fields' => $this->transactionFormFields($transaction),
            ],
        ], 200);
    }

    public function updatePortfolioTransaction(
        Request $request,
        int $id,
        UpdatePortfolioTransactionService $service
    ) {
        $request->validate([
            'security_id' => ['sometimes', 'required', 'integer'],
            'transaction_type_id' => ['sometimes', 'required', 'integer'],
            'shares' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'price_per_share' => ['sometimes', 'required', 'numeric', 'gte:0'],
            'transaction_date' => ['sometimes', 'required', 'date'],
        ]);

        try {

            $transaction = $service->update(
                Auth::id(),
                $id,
                $request->all()
            );
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 200);
    }

    public function deletePortfolioTransaction(int $id)
    {
        try {

            $transaction = PortfolioTransaction::query()
                ->select('portfolio_transactions.*')
                ->join('portfolios', 'portfolio_transactions.portfolio_id', '=', 'portfolios.id')
                ->where('portfolio_transactions.id', $id)
                ->where('portfolios.user_id', Auth::id())
                ->firstOrFail();

            $transaction->delete();
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Portfolio transaction deleted successfully.',
        ], 200);
    }

    private function transactionFormFields(?PortfolioTransaction $transaction = null): array
    {
        return [
            [
                'name' => 'security_id',
                'label' => 'Security',
                'type' => 'select',
                'required' => true,
                'value' => $transaction?->security_id,
            ],
            [
                'name' => 'transaction_type_id',
                'label' => 'Transaction Type',
                'type' => 'select',
                'required' => true,
                'value' => $transaction?->transaction_type_id,
            ],
            [
                'name' => 'shares',
                'label' => 'Shares',
                'type' => 'number',
                'required' => true,
                'step' => '0.0001',
                'min' => 0.0001,
                'value' => $transaction?->shares,
            ],
            [
                'name' => 'price_per_share',
                'label' => 'Price Per Share',
                'type' => 'number',
                'required' => true,
                'step' => '0.0001',
                'min' => 0,
                'value' => $transaction?->price_per_share,
            ],
            [
                'name' => 'transaction_date',
                'label' => 'Transaction Date',
                'type' => 'date',
                'required' => true,
                'value' => $transaction?->transaction_date?->format('Y-m-d'),
            ],
        ];
    }

    public function importPortfolioTransactions(
        Request $request,
        int $portfolio_id,
        ImportPortfolioTransactionsService $service
    ) {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {

            $results = $service->import(
                Auth::id(),
                $portfolio_id,
                $request->file('csv_file')
            );
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'The CSV format is invalid.',

                'required_columns' => [
                    'symbol',
                    'type',
                    'shares',
                    'price',
                    'date',
                ],

                'example' => [
                    'symbol' => 'SCHD',
                    'type' => 'buy',
                    'shares' => '10',
                    'price' => '75.25',
                    'date' => '2026-05-15',
                ],

            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    }

    public function deleteAllPortfolioTransactions(int $portfolio_id)
    {
        try {

            $portfolio = Portfolio::where('user_id', Auth::id())
                ->where('id', $portfolio_id)
                ->firstOrFail();

            PortfolioTransaction::where('portfolio_id', $portfolio->id)
                ->delete();
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'message' => 'All portfolio transactions deleted successfully.',

        ], 200);
    }

    public function getImportPortfolioTransactionsConfig()
    {

        $aliases = config('import_transaction_aliases.aliases');

        return response()->json([

            'success' => true,

            'data' => [

                'required_columns' => [

                    'symbol',

                    'transaction_type',

                    'shares',

                    'price_per_share',

                    'transaction_date',

                ],

                'accepted_transaction_types' => array_keys($aliases),

                'date_format' => 'Y-m-d',

                'example_row' => [

                    'symbol' => 'SCHD',

                    'transaction_type' => 'buy',

                    'shares' => '10',

                    'price_per_share' => '75.25',

                    'transaction_date' => '2026-05-15',

                ],

                'example_csv' => [

                    'symbol,transaction_type,shares,price_per_share,transaction_date',

                    'SCHD,buy,10,75.25,2026-05-15',

                    'VYM,sell,5,120.10,2026-05-16',

                ],

            ],

        ], 200);
    }

    public function exportPortfolioTransactions(
        Request $request,
        int $portfolio_id,
        ExportPortfolioTransactionsService $service
    ) {
        try {

            return $service->export(
                Auth::id(),
                $portfolio_id,
                $request->input('security_id')
            );
        } catch (\Exception $e) {

            Log::error('Failed to export portfolio transactions', [
                'user_id' => Auth::id(),
                'portfolio_id' => $portfolio_id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function csvUploadTransactions(
        Request $request,
        int $portfolio_id,
        UserBulkTransactionUploadService $service
    ) {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {

            $results = $service->import(
                Auth::id(),
                $portfolio_id,
                $request->file('csv_file')
            );
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'The CSV format is invalid.',
                'required_columns' => [
                    'symbol',
                    'transaction_type',
                    'shares',
                    'price_per_share',
                    'transaction_date',
                ],
                'example' => [
                    'symbol' => 'SCHD',
                    'transaction_type' => 'buy',
                    'shares' => '10',
                    'price_per_share' => '75.25',
                    'transaction_date' => '2026-05-15',
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    }

    public function showPortfolioTransaction(int $transaction_id)
    {
        try {

            $transaction = PortfolioTransaction::where('portfolio_transactions.id', $transaction_id)

                ->leftJoin('portfolios', 'portfolio_transactions.portfolio_id', '=', 'portfolios.id')

                ->leftJoin('securities', 'portfolio_transactions.security_id', '=', 'securities.id')

                ->where('portfolios.user_id', Auth::id())

                ->select([
                    'portfolio_transactions.*',
                    'securities.symbol',
                ])

                ->firstOrFail();
        } catch (\Exception $e) {

            Log::error('Failed to show portfolio transaction', [
                'user_id' => Auth::id(),
                'transaction_id' => $transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 200);
    }
}
