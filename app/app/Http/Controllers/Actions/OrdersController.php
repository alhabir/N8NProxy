<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Services\Salla\SallaHttpClient;
use App\Support\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class OrdersController extends Controller
{
    public function __construct(
        private SallaHttpClient $httpClient
    ) {}

    public function index(Request $request): JsonResponse
    {
        $merchantId = $this->requireMerchant($request);
        $endpoint = Endpoint::orders()['list'];
        $params = $request->except(['merchant_id', 'payload', 'order_id']);

        $result = $this->httpClient->makeRequest($merchantId, 'get', $endpoint, $params);

        return $this->respond($result);
    }

    public function show(Request $request, ?string $id = null): JsonResponse
    {
        $merchantId = $this->requireMerchant($request);
        $orderId = $id ?? $request->input('order_id');

        if (!$orderId) {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID is required.',
            ]);
        }

        $endpoint = Endpoint::expand(Endpoint::orders()['get'], ['id' => $orderId]);
        $result = $this->httpClient->makeRequest($merchantId, 'get', $endpoint);

        return $this->respond($result);
    }

    public function store(Request $request): JsonResponse
    {
        $merchantId = $this->requireMerchant($request);
        $endpoint = Endpoint::orders()['create'];
        $payload = $request->input('payload', []);

        $result = $this->httpClient->makeRequest($merchantId, 'post', $endpoint, $payload);

        return $this->respond($result);
    }

    public function update(Request $request, ?string $id = null): JsonResponse
    {
        $merchantId = $this->requireMerchant($request);
        $orderId = $id ?? $request->input('order_id');

        if (!$orderId) {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID is required.',
            ]);
        }

        $endpoint = Endpoint::expand(Endpoint::orders()['update'], ['id' => $orderId]);
        $payload = $request->input('payload', []);
        $httpMethod = strtolower($request->getMethod());

        $result = $this->httpClient->makeRequest($merchantId, $httpMethod, $endpoint, $payload);

        return $this->respond($result);
    }

    public function destroy(Request $request, ?string $id = null): JsonResponse|Response
    {
        $merchantId = $this->requireMerchant($request);
        $orderId = $id ?? $request->input('order_id');

        if (!$orderId) {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID is required.',
            ]);
        }

        $endpoint = Endpoint::expand(Endpoint::orders()['delete'], ['id' => $orderId]);
        $result = $this->httpClient->makeRequest($merchantId, 'delete', $endpoint);

        return $this->respond($result);
    }

    private function requireMerchant(Request $request): string
    {
        $merchantId = $request->input('merchant_id');

        if (!$merchantId) {
            throw ValidationException::withMessages([
                'merchant_id' => 'Merchant ID is required.',
            ]);
        }

        return $merchantId;
    }

    private function respond(array $result): JsonResponse|Response
    {
        $status = $result['status'] ?? 200;

        if ($status === 204) {
            return response()->noContent();
        }

        $payload = [
            'success' => $result['success'] ?? true,
            'status' => $status,
            'data' => $this->extractPayloadData($result['data'] ?? null),
        ];

        if (isset($result['data']['pagination'])) {
            $payload['pagination'] = $result['data']['pagination'];
        }

        if (!empty($result['headers'])) {
            $payload['headers'] = $result['headers'];
        }

        return response()->json($payload, $status);
    }

    private function extractPayloadData(mixed $data): mixed
    {
        if (is_array($data) && array_key_exists('data', $data)) {
            return $data['data'];
        }

        return $data;
    }
}