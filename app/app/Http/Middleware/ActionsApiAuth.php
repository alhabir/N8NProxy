<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionsApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');
        $expectedToken = config('app.actions_token', env('ACTIONS_API_BEARER'));

        $isValid = str_starts_with($authHeader, 'Bearer ') 
            && trim(substr($authHeader, 7)) === $expectedToken;

        if (!$isValid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
