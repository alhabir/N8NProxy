<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');

        $user = Auth::guard('admin')->user();

        if (!$user || !$user->is_admin) {
            return redirect()->guest(route('admin.login'));
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
