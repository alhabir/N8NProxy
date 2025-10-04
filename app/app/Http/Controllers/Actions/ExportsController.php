<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class ExportsController extends ActionController
{
    public function __construct(private SallaHttpClient $api) {}

    public function create(Request $request)
    {
        $this->validateMerchant($request);
        $request->validate([
            'payload' => 'required|array',
            'payload.type' => 'required|string|in:orders,products,customers',
            'payload.format' => 'sometimes|string|in:csv,xlsx',
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

    public function list(Request $request)
    {
        $this->validateMerchant($request);
        
        $query = $request->only([
            'page', 'per_page', 'type', 'status'
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

    public function status(Request $request)
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

    public function download(Request $request)
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

        // For download endpoint, we might want to handle it differently
        // If Salla returns a direct download URL, return that
        // If it returns file content, stream it
        $responseData = $response->json();
        
        if (isset($responseData['download_url'])) {
            // Return the download URL for the client to handle
            return response()->json([
                'download_url' => $responseData['download_url']
            ], $response->status());
        }

        return $this->okOrError($response);
    }
}