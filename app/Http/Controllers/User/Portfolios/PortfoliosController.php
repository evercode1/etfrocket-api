<?php

namespace App\Http\Controllers\User\Portfolios;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Queries\Portfolios\PortfolioCardSummariesQuery;
use App\Services\Portfolios\CreatePortfolioService;
use App\Services\Portfolios\ListPortfoliosService;
use App\Services\Portfolios\UpdatePortfolioService;
use App\Services\Portfolios\ViewPortfolioService;
use App\Utilities\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PortfoliosController extends Controller
{
    public function listPortfolios(ListPortfoliosService $service)
    {
        try {

            $portfolios = $service->getData(Auth::id());
        } catch (\Exception $e) {

            Log::error('Failed to list portfolios', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $portfolios,
        ], 200);
    }

    public function viewPortfolio(int $id, ViewPortfolioService $service)
    {
        try {

            $portfolio = $service->getData(Auth::id(), $id);
        } catch (\Exception $e) {

            Log::error('Failed to view portfolio', [
                'user_id' => Auth::id(),
                'portfolio_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ], 200);
    }

    public function getCreatePortfolioFormConfig()
    {
        return response()->json([

            'success' => true,

            'data' => [

                'fields' => [

                    [
                        'name' => 'portfolio_name',
                        'label' => 'Portfolio Name',
                        'type' => 'text',
                        'required' => true,
                        'max_length' => 255,
                        'placeholder' => 'My Dividend Portfolio',
                    ],

                    [
                        'name' => 'is_default',
                        'label' => 'Default Portfolio',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                    ],

                ],

            ],

        ], 200);
    }

    public function createPortfolio(Request $request, CreatePortfolioService $service)
    {
        $request->validate([

            'portfolio_name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],

        ]);

        try {

            $portfolio = $service->create(Auth::id(), $request->all());
        } catch (\Exception $e) {

            Log::error('Failed to create portfolio', [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ], 201);
    }

    public function getUpdatePortfolioFormConfig(int $id)
    {
        try {

            $portfolio = Portfolio::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();
        } catch (\Exception $e) {

            Log::error('Failed to load update portfolio form config', [

                'user_id' => Auth::id(),

                'portfolio_id' => $id,

                'error' => $e->getMessage(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => [

                'portfolio_id' => $portfolio->id,

                'fields' => [

                    [
                        'name' => 'portfolio_name',
                        'label' => 'Portfolio Name',
                        'type' => 'text',
                        'required' => true,
                        'max_length' => 255,
                        'placeholder' => 'My Dividend Portfolio',
                        'value' => $portfolio->portfolio_name,
                    ],

                    [
                        'name' => 'is_default',
                        'label' => 'Default Portfolio',
                        'type' => 'boolean',
                        'required' => false,
                        'value' => (bool) $portfolio->is_default,
                    ],

                ],

            ],

        ], 200);
    }

    public function updatePortfolio(Request $request, int $id, UpdatePortfolioService $service)
    {
        $request->validate([

            'portfolio_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],

        ]);

        try {

            $portfolio = $service->update(Auth::id(), $id, $request->all());
        } catch (\Exception $e) {

            Log::error('Failed to update portfolio', [
                'user_id' => Auth::id(),
                'portfolio_id' => $id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ], 200);
    }

    public function deletePortfolio(int $id)
    {
        try {

            DB::transaction(function () use ($id) {

                $portfolio = Portfolio::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->firstOrFail();

                PortfolioTransaction::where('portfolio_id', $portfolio->id)
                    ->delete();

                $portfolio->delete();
            });
        } catch (\Exception $e) {

            Log::error('Failed to delete portfolio', [
                'user_id' => Auth::id(),
                'portfolio_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Portfolio deleted successfully.',
        ], 200);
    }

    public function portfolioCardSummaries(PortfolioCardSummariesQuery $query)
    {
        try {

            $user_id = Auth::id();

            $summaries = $query->getData(Auth::id());
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,
                'message' => 'An error occurred while fetching portfolio summaries.',

            ], 500);
        }

        return response()->json([

            'success' => true,
            'data' => $summaries,

        ], 200);
    }
}
