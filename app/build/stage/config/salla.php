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
        // Order core
        'order.created','order.updated','order.cancelled','order.deleted',
        'order.refunded','order.payment.updated','order.status.updated',
        'order.products.updated','order.coupon.updated','order.total.price.updated',
        // Order shipments and shipping address
        'order.shipment.creating','order.shipment.created','order.shipment.cancelled',
        'order.shipment.return.creating','order.shipment.return.created','order.shipment.return.cancelled',
        'order.shipping.address.updated',
        // Customer
        'customer.created','customer.updated','customer.login','customer.otp.request',
        // Product
        'product.created','product.updated','product.deleted','product.available','product.quantity.low',
        // Category
        'category.created','category.updated',
        // Brand
        'brand.created','brand.updated','brand.deleted',
        // Cart / coupons
        'abandoned.cart','coupon.applied',
        // Invoice
        'invoice.created',
        // Special offers
        'specialoffer.created','specialoffer.updated',
        // Store / Branch / Tax
        'store.branch.created','store.branch.updated','store.branch.setDefault','store.branch.activated','store.branch.deleted','storetax.created',
        // Shipping zones & companies
        'shipping.zone.created','shipping.zone.updated','shipping.company.created','shipping.company.updated','shipping.company.deleted',
        // Reviews
        'review.added',
    ],
];


