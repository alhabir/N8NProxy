<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponsController extends ActionController
{
    public function __construct(
        private SallaHttpClient $api
    ) {
    }

    /**
     * Create a new coupon
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
            config('salla_api.coupons.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    /**
     * Delete a coupon
     */
    public function delete(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'coupon_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'DELETE',
            config('salla_api.coupons.delete'),
            ['id' => $request->coupon_id]
        );

        return $this->okOrError($response);
    }

    /**
     * Get a single coupon
     */
    public function get(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'coupon_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.coupons.get'),
            ['id' => $request->coupon_id]
        );

        return $this->okOrError($response);
    }

    /**
     * List coupons with optional filters
     */
    public function list(Request $request): JsonResponse
    {
        $this->validateMerchant($request);

        $query = $request->only([
            'page',
            'per_page',
            'status',
            'type',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.coupons.list'),
            [],
            null,
            $query
        );

        return $this->okOrError($response);
    }

    /**
     * Update a coupon
     */
    public function update(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'coupon_id' => 'required',
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'PATCH',
            config('salla_api.coupons.update'),
            ['id' => $request->coupon_id],
            $request->payload
        );

        return $this->okOrError($response);
    }
}
