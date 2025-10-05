<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionsApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Missing or invalid authorization header'], 401);
        }

        $token = substr($authHeader, 7);
        $expectedToken = config('app.actions_api_bearer') ?: config('app.actions_token');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        return $next($request);
    }
}