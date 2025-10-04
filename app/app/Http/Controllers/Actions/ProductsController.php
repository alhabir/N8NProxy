<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductsController extends ActionController
{
    public function __construct(
        private SallaHttpClient $api
    ) {
    }

    /**
     * Create a new product
     */
    public function create(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'POST',
            config('salla_api.products.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    /**
     * Delete a product
     */
    public function delete(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'product_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'DELETE',
            config('salla_api.products.delete'),
            ['id' => $request->product_id]
        );

        return $this->okOrError($response);
    }

    /**
     * Get a single product
     */
    public function get(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'product_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.products.get'),
            ['id' => $request->product_id]
        );

        return $this->okOrError($response);
    }

    /**
     * List products with optional filters
     */
    public function list(Request $request): JsonResponse
    {
        $this->validateMerchant($request);

        $query = $request->only([
            'page',
            'per_page',
            'status',
            'category',
            'search',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.products.list'),
            [],
            null,
            $query
        );

        return $this->okOrError($response);
    }

    /**
     * Update a product
     */
    public function update(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'product_id' => 'required',
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'PATCH',
            config('salla_api.products.update'),
            ['id' => $request->product_id],
            $request->payload
        );

        return $this->okOrError($response);
    }
}
