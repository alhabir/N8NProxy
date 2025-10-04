<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportsController extends ActionController
{
    public function __construct(
        private SallaHttpClient $api
    ) {}

    /**
     * Create a new export job
     */
    public function create(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'payload' => 'required|array',
            'payload.type' => 'required|string|in:orders,products,customers,coupons,categories',
            'payload.format' => 'required|string|in:csv,xlsx',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'POST',
            config('salla_api.exports.create'),
            [],
            $request->payload
        );

        return $this->okOrError($response);
    }

    /**
     * List export jobs
     */
    public function list(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page',
            'per_page',
            'type',
            'status',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.exports.list'),
            [],
            null,
            $query
        );

        return $this->okOrError($response);
    }

    /**
     * Get export job status
     */
    public function status(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'export_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.exports.status'),
            ['id' => $request->export_id]
        );

        return $this->okOrError($response);
    }

    /**
     * Download export file
     */
    public function download(Request $request): JsonResponse
    {
        $this->validateMerchant($request);
        $request->validate([
            'export_id' => 'required',
        ]);

        $response = $this->api->call(
            $request->merchant_id,
            'GET',
            config('salla_api.exports.download'),
            ['id' => $request->export_id]
        );

        // If the response contains a download URL, return it
        // Otherwise, return the response as-is (might be an error)
        return $this->okOrError($response);
    }
}