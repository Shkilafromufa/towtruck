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
            Log::info('Проверка роли:', ['role' => $user->role]);

            if ($user->role !== $role) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка токена:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token error'], 401);
        }

        return $next($request);
    }
}
