<?php

return [
    'signature_header' => 'X-Salla-Signature',

    'paths' => [
        'merchant_id' => 'data.store.id',
        'event_name' => 'event',
        'event_id' => 'id',
    ],

    'paths_overrides' => [
        // 'product.*' => ['merchant_id' => 'data.store.id'],
    ],

    'supported_events' => [
        'order.created', 'order.updated', 'order.cancelled', 'order.deleted',
        'order.refunded', 'order.payment.updated', 'order.status.updated',
        'order.products.updated', 'order.coupon.updated', 'order.total.price.updated',
        'order.shipment.creating','order.shipment.created','order.shipment.cancelled',
        'order.shipment.return.creating','order.shipment.return.created','order.shipment.return.cancelled',
        'customer.created','customer.updated','customer.login','customer.otp.request',
    ],
];


