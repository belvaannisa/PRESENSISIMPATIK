<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-KEY') != env('API_KEY')) {

            return response()->json([
                'success'=>false,
                'message'=>'Unauthorized'
            ],401);

        }

        return $next($request);
    }
}