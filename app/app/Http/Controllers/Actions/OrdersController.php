<?php

namespace App\Http\Controllers\Actions;

use App\Services\Salla\SallaHttpClient;
use Illuminate\Http\Request;

class OrdersController extends ActionController
{
    public function __construct(private SallaHttpClient $api) {}

    public function create(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['payload' => 'required|array']);
        $res = $this->api->call($r->merchant_id, 'POST', (string) config('salla_api.orders.create'), [], $r->input('payload'));
        return $this->okOrError($res);
    }

    public function delete(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['order_id' => 'required']);
        $res = $this->api->call($r->merchant_id, 'DELETE', (string) config('salla_api.orders.delete'), ['id' => $r->input('order_id')]);
        return $this->okOrError($res);
    }

    public function get(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['order_id' => 'required']);
        $res = $this->api->call($r->merchant_id, 'GET', (string) config('salla_api.orders.get'), ['id' => $r->input('order_id')]);
        return $this->okOrError($res);
    }

    public function list(Request $r)
    {
        $this->validateMerchant($r);
        $query = $r->only(['merchant_id','page','per_page','status','payment_status','date_from','date_to']);
        unset($query['merchant_id']);
        $res = $this->api->call($r->merchant_id, 'GET', (string) config('salla_api.orders.list'), [], null, $query);
        return $this->okOrError($res);
    }

    public function update(Request $r)
    {
        $this->validateMerchant($r);
        $r->validate(['order_id' => 'required', 'payload' => 'required|array']);
        $res = $this->api->call($r->merchant_id, 'PATCH', (string) config('salla_api.orders.update'), ['id' => $r->input('order_id')], $r->input('payload'));
        return $this->okOrError($res);
    }
}
