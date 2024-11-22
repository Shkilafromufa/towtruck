<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class VerifyRole
{
    public function handle($request, Closure $next, $role)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if ($user->role !== $role) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token error: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}