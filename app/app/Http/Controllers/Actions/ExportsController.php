<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class ExportsController extends ActionController
{
    public function __construct(private SallaHttpClient $api) {}

    public function create(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate([
            'payload' => 'required|array',
            'payload.type' => 'required|string',
            'payload.format' => 'required|string',
        ]);
        $res = $this->api->call($r->merchant_id, 'POST', (string) config('salla_api.exports.create'), [], $r->input('payload'));
        return $this->okOrError($res);
    }

    public function list(Request $r)
    {
        $this->validateMerchant($r);
        $query = $r->only(['merchant_id','page','per_page','type','status']);
        unset($query['merchant_id']);
        $res = $this->api->call($r->merchant_id, 'GET', (string) config('salla_api.exports.list'), [], null, $query);
        return $this->okOrError($res);
    }

    public function status(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['export_id' => 'required']);
        $res = $this->api->call($r->merchant_id, 'GET', (string) config('salla_api.exports.status'), ['id' => $r->input('export_id')]);
        return $this->okOrError($res);
    }

    public function download(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['export_id' => 'required']);
        $res = $this->api->call($r->merchant_id, 'GET', (string) config('salla_api.exports.download'), ['id' => $r->input('export_id')]);
        return $this->okOrError($res);
    }
}
