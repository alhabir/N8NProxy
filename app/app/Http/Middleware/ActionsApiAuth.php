<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActionsApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization', '');
        
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = trim(substr($authHeader, 7));
        $expectedToken = config('app.actions_token', env('ACTIONS_API_BEARER'));

        if (empty($expectedToken) || $token !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}