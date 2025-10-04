<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class ProductsController extends ActionController
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
            config('salla_api.products.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    public function delete(Request $request)
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

    public function get(Request $request)
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

    public function list(Request $request)
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page', 'per_page', 'search', 'status', 'category_id',
            'sort_by', 'sort_direction', 'price_from', 'price_to'
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

    public function update(Request $request)
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