<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomersController extends ActionController
{
    public function __construct(
        private SallaHttpClient $api
    ) {
    }

    /**
     * Delete a customer
     */
    public function delete(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'customer_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'DELETE',
            config('salla_api.customers.delete'),
            ['id' => $request->customer_id]
        );

        return $this->okOrError($response);
    }

    /**
     * Get a single customer
     */
    public function get(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'customer_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.customers.get'),
            ['id' => $request->customer_id]
        );

        return $this->okOrError($response);
    }

    /**
     * List customers with optional filters
     */
    public function list(Request $request): JsonResponse
    {
        $this->validateMerchant($request);

        $query = $request->only([
            'page',
            'per_page',
            'search',
            'country',
            'city',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.customers.list'),
            [],
            null,
            $query
        );

        return $this->okOrError($response);
    }

    /**
     * Update a customer
     */
    public function update(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'customer_id' => 'required',
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'PATCH',
            config('salla_api.customers.update'),
            ['id' => $request->customer_id],
            $request->payload
        );

        return $this->okOrError($response);
    }
}
