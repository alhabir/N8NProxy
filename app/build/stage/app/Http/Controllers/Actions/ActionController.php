<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ActionController extends Controller
{
    /**
     * Return the Salla API response as-is (preserving status code)
     *
     * @param Response $response
     * @return JsonResponse
     */
    protected function okOrError(Response $response): JsonResponse
    {
        return response()->json($response->json(), $response->status());
    }

    /**
     * Validate that merchant_id is provided in the request
     *
     * @param Request $request
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateMerchant(Request $request): void
    {
        $request->validate([
            'merchant_id' => 'required|string',
        ]);
    }
}