<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Services\Salla\SallaHttpClient;
use App\Support\Endpoint;
use Illuminate\Http\Request;

class ExportsController extends Controller
{
    public function __construct(
        private SallaHttpClient $httpClient
    ) {}

    public function index(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::exports()['list'], $request->all());
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function store(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::exports()['create']);
        
        return $this->httpClient->makeRequest($merchantId, 'post', $endpoint, $request->all());
    }

    public function status(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::exports()['status'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }

    public function download(Request $request, string $id)
    {
        $merchantId = $request->input('merchant_id');
        $endpoint = Endpoint::expand(Endpoint::exports()['download'], ['id' => $id]);
        
        return $this->httpClient->makeRequest($merchantId, 'get', $endpoint);
    }
}