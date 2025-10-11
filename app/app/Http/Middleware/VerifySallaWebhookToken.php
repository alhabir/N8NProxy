<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySallaWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = env('SALLA_WEBHOOK_TOKEN');

        if (! is_string($expectedToken) || trim($expectedToken) === '') {
            $expectedToken = config('salla.webhook.token');
        }

        $expected = is_string($expectedToken) ? trim($expectedToken) : null;

        $providedToken = $request->header('X-Webhook-Token')
            ?? $request->header('X-Salla-Webhook-Token')
            ?? $request->query('token');
        $provided = is_string($providedToken) ? trim($providedToken) : null;

        if ($expected === null || $expected === '' || $provided === null || $provided === '' || $provided !== $expected) {
            Log::warning('Invalid Salla webhook token', [
                'has_header' => $request->hasHeader('X-Webhook-Token'),
                'has_alt_header' => $request->hasHeader('X-Salla-Webhook-Token'),
                'has_query' => $request->query('token') !== null,
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'invalid-token',
            ], 401);
        }

        return $next($request);
    }
}
