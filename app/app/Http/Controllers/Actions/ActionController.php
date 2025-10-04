<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ActionController extends Controller
{
    /**
     * Return response with same status code as Salla API response
     *
     * @param HttpResponse $response
     * @return JsonResponse
     */
    protected function okOrError(HttpResponse $response): JsonResponse
    {
        return response()->json($response->json(), $response->status());
    }

    /**
     * Validate that merchant_id is present in request
     *
     * @param Request $request
     * @return void
     */
    protected function validateMerchant(Request $request): void
    {
        $request->validate([
            'merchant_id' => 'required|string',
        ]);
    }
}
