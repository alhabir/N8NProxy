<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Services\Salla\SallaHttpClient;
use App\Support\Endpoint;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function __construct(
        private SallaHttpClient $httpClient
    ) {}

    public function index(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::categories()['list'], $request->all());
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function show(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::categories()['get'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function store(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::categories()['create']);
        
        return $this->httpClient->makeRequest($merchantId, 'post', $endpoint, $request->all());
    }

    public function update(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::categories()['update'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'put', $endpoint, $request->all());
    }

    public function destroy(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::categories()['delete'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'delete', $endpoint);
    }
}