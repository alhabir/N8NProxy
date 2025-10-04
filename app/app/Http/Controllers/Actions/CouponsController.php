<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class CouponsController extends ActionController
{
    public function __construct(private SallaHttpClient $api) {}

    public function create(Request $request)
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

    public function delete(Request $request)
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

    public function get(Request $request)
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

    public function list(Request $request)
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page', 'per_page', 'search', 'status', 'type',
            'date_from', 'date_to'
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

    public function update(Request $request)
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