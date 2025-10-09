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
        $mode = config('salla.webhook.mode', 'token');

        if ($mode === 'token') {
            $expected = (string) config('salla.webhook.token');
            $headerName = config('salla.webhook.token_header', 'X-Webhook-Token');
            $queryKey = config('salla.webhook.token_query_key', 'token');

            $provided = $request->header($headerName) ?? $request->query($queryKey);

            if (empty($expected) || ! is_string($provided) || ! hash_equals($expected, $provided)) {
                Log::warning('Salla webhook rejected due to invalid token', [
                    'path' => $request->path(),
                    'mode' => $mode,
                    'has_header' => $request->headers->has($headerName),
                    'has_query' => $request->query->has($queryKey),
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => 'invalid_token',
                ], 401);
            }
        }

        return $next($request);
    }
}
