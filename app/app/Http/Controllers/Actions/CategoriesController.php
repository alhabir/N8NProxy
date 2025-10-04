<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class CategoriesController extends ActionController
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
            config('salla_api.categories.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    public function delete(Request $request)
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

    public function get(Request $request)
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

    public function list(Request $request)
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page', 'per_page', 'search', 'parent_id', 'status'
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

    public function update(Request $request)
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