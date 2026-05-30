<?php

namespace App\Http\Controllers\User\Securities;

use App\Http\Controllers\Controller;
use App\Queries\Securities\SecuritiesHoverCardQuery;

class SecuritiesHoverCardController extends Controller
{
    public function securityHoverCard(
        string $symbol,
        SecuritiesHoverCardQuery $query
    ) {
        try {

            $data = $query->getData(
                $symbol
            );

        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Security not found.',

            ], 404);
        }

        return response()->json([

            'success' => true,

            'data' => $data,

        ]);
    }
}
