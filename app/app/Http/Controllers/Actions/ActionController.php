<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;

abstract class ActionController extends Controller
{
    protected function okOrError(Response $res)
    {
        $status = $res->status();
        $contentType = $res->header('Content-Type', 'application/json');
        $body = $res->body();

        // If body is valid JSON, return as JSON; otherwise return raw
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return response()->json($json, $status);
        }

        return response($body, $status, ['Content-Type' => $contentType]);
    }

    protected function validateMerchant(Request $r): void
    {
        $r->validate(['merchant_id' => 'required|string']);
    }
}
