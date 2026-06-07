<?php

namespace App\Http\Controllers\Public\HealthCheck;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function check(Request $request)
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'healthy',
        ]);
    }
}
