<?php

namespace App\Http\Controllers\User\MissionControl;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use App\Queries\MissionControl\MissionControlQuery;
use App\Utilities\Auth;

class MissionControlController extends Controller
{
    public function index(Request $request)
    {
        try {

            $mission_control = (new MissionControlQuery())->getData($request->input('portfolio_id'));
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,
                'message' => 'An error occurred while fetching mission control data.',

            ], 500);
        }

        return response()->json([

            'success' => true,
            'data' => $mission_control

        ], 200);
    }

    public function getPortfolioSelects()
    {
        try {

            $user_id = Auth::id();

            $selects = Portfolio::where('user_id', $user_id)

                ->pluck('portfolio_name', 'id')

                ->toArray();
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,
                'message' => 'An error occurred while fetching mission control data.',

            ], 500);
        }

        return response()->json([

            'success' => true,
            'data' => $selects

        ], 200);
    }
}
