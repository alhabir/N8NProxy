<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActionsApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Authorization', '');
        $ok = str_starts_with($header, 'Bearer ') && trim(substr($header, 7)) === (string) (config('app.actions_token', env('ACTIONS_API_BEARER')));
        if (!$ok) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
