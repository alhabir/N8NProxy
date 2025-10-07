<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
            'webhooks/salla',
            'app-events/*',
            'api/actions/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
