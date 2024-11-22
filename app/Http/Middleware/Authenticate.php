<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    use Illuminate\Support\Facades\Log;
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return null; // Убираем попытку перенаправления
        }
    }
    public function handle($request, Closure $next)
    {
        Log::info('JWT аутентификация', ['headers' => $request->headers->all()]);
    
        if (Auth::guard('api')->check()) {
            return $next($request);
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
