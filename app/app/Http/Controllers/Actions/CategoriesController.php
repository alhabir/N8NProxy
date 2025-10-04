<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriesController extends ActionController
{
    public function __construct(
        private SallaHttpClient $api
    ) {
    }

    /**
     * Create a new category
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
            config('salla_api.categories.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    /**
     * Delete a category
     */
    public function delete(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'category_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'DELETE',
            config('salla_api.categories.delete'),
            ['id' => $request->category_id]
        );

        return $this->okOrError($response);
    }

    /**
     * Get a single category
     */
    public function get(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'category_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.categories.get'),
            ['id' => $request->category_id]
        );

        return $this->okOrError($response);
    }

    /**
     * List categories with optional filters
     */
    public function list(Request $request): JsonResponse
    {
        $this->validateMerchant($request);

        $query = $request->only([
            'page',
            'per_page',
            'parent_id',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.categories.list'),
            [],
            null,
            $query
        );

        return $this->okOrError($response);
    }

    /**
     * Update a category
     */
    public function update(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'category_id' => 'required',
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'PATCH',
            config('salla_api.categories.update'),
            ['id' => $request->category_id],
            $request->payload
        );

        return $this->okOrError($response);
    }
}
