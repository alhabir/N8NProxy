<?php

use Illuminate\Support\Facades\Http;

it('rejects missing or bad bearer for actions api', function () {
    $res = $this->getJson('/api/actions/orders/list?merchant_id=123');
    $res->assertStatus(401);

    $res2 = $this->withHeader('Authorization', 'Bearer wrong')->getJson('/api/actions/orders/list?merchant_id=123');
    $res2->assertStatus(401);
});
