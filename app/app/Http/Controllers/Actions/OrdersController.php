<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class OrdersController extends ActionController
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
            config('salla_api.orders.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    public function delete(Request $request)
    {
        $this->validateMerchant($request);
        $request->validate([
            'order_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'DELETE',
            config('salla_api.orders.delete'),
            ['id' => $request->order_id]
        );

        return $this->okOrError($response);
    }

    public function get(Request $request)
    {
        $this->validateMerchant($request);
        $request->validate([
            'order_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.orders.get'),
            ['id' => $request->order_id]
        );

        return $this->okOrError($response);
    }

    public function list(Request $request)
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page', 'per_page', 'status', 'payment_status', 
            'date_from', 'date_to', 'search'
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.orders.list'),
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
            'order_id' => 'required',
            'payload' => 'required|array',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'PATCH',
            config('salla_api.orders.update'),
            ['id' => $request->order_id],
            $request->payload
        );

        return $this->okOrError($response);
    }
}