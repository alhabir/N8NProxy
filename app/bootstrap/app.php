<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'actions.auth' => \App\Http\Middleware\ActionsApiAuth::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            $adminHost = config('panels.admin_domain');
            $host = $request->getHost();

            if ($request->route()?->named('admin.*') || $host === $adminHost || Str::endsWith($host, '.' . ltrim($adminHost, '.'))) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->validateCsrfTokens(except: [
            'webhooks/salla/app-events',
            'api/actions/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your session expired. Please refresh the page and try again.'),
                ], 419);
            }

            $adminDomain = config('panels.admin_domain');
            $host = $request->getHost();
            $adminLoginRoute = 'admin.login';
            $loginRouteName = $adminDomain && ($request->route()?->named('admin.*')
                || $host === $adminDomain
                || Str::endsWith($host, '.' . ltrim($adminDomain, '.')))
                ? $adminLoginRoute
                : 'login';

            return redirect()->guest(route($loginRouteName))
                ->with('warning', __('Your session expired. Please log in again.'));
        });
    })->create();
