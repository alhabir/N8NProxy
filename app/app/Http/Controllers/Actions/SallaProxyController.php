<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SallaProxyController extends Controller
{
    public function __construct(
        private SallaHttpClient $client
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'string'],
            'method' => ['required', 'string'],
            'path' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $method = strtoupper($validated['method']);

        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw ValidationException::withMessages([
                'method' => 'Unsupported HTTP method.',
            ]);
        }

        $merchant = Merchant::query()
            ->where('id', $validated['merchant_id'])
            ->orWhere('salla_merchant_id', $validated['merchant_id'])
            ->first();

        if (! $merchant || ! $merchant->salla_merchant_id) {
            return response()->json([
                'ok' => false,
                'error' => 'merchant_not_found',
            ], 404);
        }

        if (! $merchant->salla_access_token && ! optional($merchant->token)->access_token) {
            return response()->json([
                'ok' => false,
                'error' => 'merchant_not_connected',
            ], 409);
        }

        $path = '/' . ltrim($validated['path'], '/');
        $payload = $validated['payload'] ?? [];

        try {
            $result = $this->client->makeRequest(
                $merchant->salla_merchant_id,
                strtolower($method),
                $path,
                $payload
            );

            return response()->json([
                'ok' => $result['success'],
                'status' => $result['status'],
                'data' => $result['data'],
                'headers' => $result['headers'],
            ], $result['status']);
        } catch (\Throwable $exception) {
            Log::error('Salla actions proxy failed', [
                'merchant_id' => $merchant->id,
                'salla_merchant_id' => $merchant->salla_merchant_id,
                'method' => $method,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'salla_request_failed',
                'message' => $exception->getMessage(),
            ], 502);
        }
    }
}
