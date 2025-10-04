<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

abstract class ActionController extends Controller
{
    /**
     * Return JSON response with same status as Salla API response
     */
    protected function okOrError(Response $response): \Illuminate\Http\JsonResponse
    {
        return response()->json($response->json(), $response->status());
    }

    /**
     * Validate that merchant_id is present in request
     */
    protected function validateMerchant(Request $request): void
    {
        $request->validate([
            'merchant_id' => 'required|string',
        ]);
    }
}