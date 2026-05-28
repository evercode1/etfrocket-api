<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowSeeds
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (! $this->allowSeeds()) {

            return response()->json(['code' => 401, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);

    }

    private function allowSeeds()
    {

        return env('ALLOW_SEEDS');

    }
}
