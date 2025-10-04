#!/bin/bash

# Test all Salla events systematically
# Make sure to execute the n8n workflow first to arm the webhook

echo "🚀 Testing All Salla Events"
echo "=========================="
echo ""

# Base URL for the n8n webhook
WEBHOOK_URL="https://n8nai.takaful-alarabia.com/webhook-test/webhook/salla"

# Test function
test_event() {
    local fixture_file=$1
    local event_name=$2
    local description=$3
    
    echo "📋 Testing: $description"
    echo "   Event: $event_name"
    echo "   Fixture: $fixture_file"
    
    # Read the fixture file
    RAW=$(cat "tests/Fixtures/salla/$fixture_file")
    
    # Generate unique event ID
    EID="wh_${event_name}_$(date +%s)_$$"
    MID=112233
    
    # Compute checksum
    CHECKSUM=$(printf "%s" "$RAW" | openssl dgst -sha256 -binary | xxd -p -c 256)
    
    # Make the request
    echo "   Sending request..."
    RESPONSE=$(curl -sS -X POST "$WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -H "X-Forwarded-By: n8n-ai-salla-proxy" \
        -H "X-Salla-Event: $event_name" \
        -H "X-Salla-Event-Id: $EID" \
        -H "X-Salla-Merchant-Id: $MID" \
        -H "X-Event-Checksum: $CHECKSUM" \
        --data-binary "$RAW" 2>&1)
    
    # Check if successful
    if echo "$RESPONSE" | grep -q '"success":true'; then
        echo "   ✅ SUCCESS: Event processed"
        echo "   Response: $(echo "$RESPONSE" | head -c 100)..."
    elif echo "$RESPONSE" | grep -q '"code":404'; then
        echo "   ⚠️  WEBHOOK DISARMED: Please click 'Execute workflow' in n8n"
    elif echo "$RESPONSE" | grep -q '"code":500'; then
        echo "   ❌ SERVER ERROR: Check n8n workflow"
        echo "   Response: $RESPONSE"
    else
        echo "   ❓ UNKNOWN RESPONSE: $RESPONSE"
    fi
    
    echo ""
    sleep 1
}

# Test all event types
echo "🛒 ORDER EVENTS"
echo "==============="
test_event "order.created.json" "order.created" "Order Created"
test_event "order.updated.json" "order.updated" "Order Updated"
test_event "order.cancelled.json" "order.cancelled" "Order Cancelled"
test_event "order.deleted.json" "order.deleted" "Order Deleted"
test_event "order.refunded.json" "order.refunded" "Order Refunded"
test_event "order.payment.updated.json" "order.payment.updated" "Order Payment Updated"
test_event "order.status.updated.json" "order.status.updated" "Order Status Updated"
test_event "order.shipment.created.json" "order.shipment.created" "Order Shipment Created"

echo "👤 CUSTOMER EVENTS"
echo "=================="
test_event "customer.created.json" "customer.created" "Customer Created"
test_event "customer.updated.json" "customer.updated" "Customer Updated"
test_event "customer.login.json" "customer.login" "Customer Login"
test_event "customer.otp.request.json" "customer.otp.request" "Customer OTP Request"

echo "📦 PRODUCT EVENTS"
echo "================="
test_event "product.created.json" "product.created" "Product Created"
test_event "product.updated.json" "product.updated" "Product Updated"
test_event "product.deleted.json" "product.deleted" "Product Deleted"
test_event "product.available.json" "product.available" "Product Available"
test_event "product.quantity.low.json" "product.quantity.low" "Product Quantity Low"

echo "📂 CATEGORY EVENTS"
echo "=================="
test_event "category.created.json" "category.created" "Category Created"
test_event "category.updated.json" "category.updated" "Category Updated"

echo "🏷️  BRAND EVENTS"
echo "================"
test_event "brand.created.json" "brand.created" "Brand Created"
test_event "brand.updated.json" "brand.updated" "Brand Updated"
test_event "brand.deleted.json" "brand.deleted" "Brand Deleted"

echo "🛒 CART EVENTS"
echo "=============="
test_event "abandoned.cart.json" "abandoned.cart" "Abandoned Cart"
test_event "coupon.applied.json" "coupon.applied" "Coupon Applied"

echo "🧾 INVOICE EVENTS"
echo "================="
test_event "invoice.created.json" "invoice.created" "Invoice Created"

echo "🎁 SPECIAL OFFER EVENTS"
echo "======================="
test_event "specialoffer.created.json" "specialoffer.created" "Special Offer Created"
test_event "specialoffer.updated.json" "specialoffer.updated" "Special Offer Updated"

echo "⭐ REVIEW EVENTS"
echo "================"
test_event "review.added.json" "review.added" "Review Added"

echo "🎉 All events tested!"
echo ""
echo "📊 SUMMARY:"
echo "==========="
echo "✅ Order Events: 8 tested"
echo "✅ Customer Events: 4 tested"
echo "✅ Product Events: 5 tested"
echo "✅ Category Events: 2 tested"
echo "✅ Brand Events: 3 tested"
echo "✅ Cart Events: 2 tested"
echo "✅ Invoice Events: 1 tested"
echo "✅ Special Offer Events: 2 tested"
echo "✅ Review Events: 1 tested"
echo ""
echo "Total: 28 event types tested"
echo ""
echo "🔧 To test more events, create additional fixtures in tests/Fixtures/salla/"
echo "📋 Supported events in proxy: 40+ event types"
