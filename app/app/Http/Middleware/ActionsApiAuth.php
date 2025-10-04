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
        
        // Check if header starts with "Bearer " and has the correct token
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized - Missing Bearer token'], 401);
        }
        
        $providedToken = trim(substr($authHeader, 7));
        
        if ($providedToken !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized - Invalid token'], 401);
        }
        
        return $next($request);
    }
}