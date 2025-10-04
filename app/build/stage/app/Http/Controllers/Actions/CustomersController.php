<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Services\Salla\SallaHttpClient;
use App\Support\Endpoint;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    public function __construct(
        private SallaHttpClient $httpClient
    ) {}

    public function index(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::customers()['list'], $request->all());
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function show(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::customers()['get'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function update(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::customers()['update'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'put', $endpoint, $request->all());
    }

    public function destroy(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::customers()['delete'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'delete', $endpoint);
    }
}