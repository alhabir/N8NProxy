# N8NProxy – Salla Webhook Proxy & Actions API

This Laravel 11 app receives Salla webhooks, validates signatures, stores deliveries for idempotency/retry/debugging, and forwards payloads to each merchant’s n8n webhook URL. Phase 1 supports Order and Customer events; new events are added by editing `config/salla.php` only.

## Requirements
- PHP 8.2+
- Laravel 11+
- MySQL (SQLite works locally)

## Environment
Add to `.env`:

```
APP_NAME=N8NProxy
APP_URL=http://127.0.0.1:8000

# Salla App keys (from your dashboard)
SALLA_CLIENT_ID=a5500786-2c22-4ca2-bab9-4cc6e0cd4906
SALLA_CLIENT_SECRET=f9b1a18cec45ac342bb9cf7bfd45ac73

# Webhook validation
SALLA_WEBHOOK_SECRET=519dd95fbd631b78020de2e36ae116c3

# Salla Admin API Base (override if needed)
SALLA_API_BASE=https://api.salla.dev/admin/v2
SALLA_OAUTH_TOKEN_URL=https://accounts.salla.sa/oauth2/token

# Proxy forwarding
FORWARD_DEFAULT_TIMEOUT_MS=6000
FORWARD_SYNC_RETRIES=2
FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
ALLOW_TEST_MODE=true

# Actions API protection (simple shared token)
ACTIONS_API_BEARER=change_me_strong_random_token
```

Run migrations:

```
php artisan migrate
```

## Config
`config/salla.php`:
- `signature_header`: `X-Salla-Signature`
- dot-paths: `merchant_id = data.store.id`, `event_name = event`, `event_id = id`
- `supported_events`: Order + Customer; append strings to extend
- optional `paths_overrides` per event prefix (e.g., `product.*`)

## Routes

### Webhooks (Triggers)
- `POST /api/webhook/salla` — main webhook ingest
- `POST /api/app-events/authorized` — capture OAuth tokens from Salla app events
- `POST /api/app-events/installed` — track app installations

### Actions API (Protected by Bearer token)
All actions endpoints require `Authorization: Bearer {ACTIONS_API_BEARER}` header.

#### Orders
- `POST /api/actions/orders/create` — Create order
- `DELETE /api/actions/orders/delete` — Delete order
- `GET /api/actions/orders/get` — Get single order
- `GET /api/actions/orders/list` — List orders
- `PATCH /api/actions/orders/update` — Update order

#### Products
- `POST /api/actions/products/create` — Create product
- `DELETE /api/actions/products/delete` — Delete product
- `GET /api/actions/products/get` — Get single product
- `GET /api/actions/products/list` — List products
- `PATCH /api/actions/products/update` — Update product

#### Customers
- `DELETE /api/actions/customers/delete` — Delete customer
- `GET /api/actions/customers/get` — Get single customer
- `GET /api/actions/customers/list` — List customers
- `PATCH /api/actions/customers/update` — Update customer

#### Marketing/Coupons
- `POST /api/actions/marketing/coupons/create` — Create coupon
- `DELETE /api/actions/marketing/coupons/delete` — Delete coupon
- `GET /api/actions/marketing/coupons/get` — Get single coupon
- `GET /api/actions/marketing/coupons/list` — List coupons
- `PATCH /api/actions/marketing/coupons/update` — Update coupon

#### Categories
- `POST /api/actions/categories/create` — Create category
- `DELETE /api/actions/categories/delete` — Delete category
- `GET /api/actions/categories/get` — Get single category
- `GET /api/actions/categories/list` — List categories
- `PATCH /api/actions/categories/update` — Update category

#### Exports
- `POST /api/actions/exports/create` — Create export job
- `GET /api/actions/exports/list` — List export jobs
- `GET /api/actions/exports/status` — Get export status
- `GET /api/actions/exports/download` — Download export file

## Flow
1) Verify signature: base64(hmac-sha256(raw_body, `SALLA_WEBHOOK_SECRET`)) in header `X-Salla-Signature`.
2) Extract event, event id, merchant id via config dot-paths.
3) Upsert `merchants` by `salla_merchant_id` (inactive placeholder if new).
4) Idempotent insert into `webhook_events` by `salla_event_id` (fallback sha256(headers+raw)).
5) Skip if signature invalid, merchant inactive, or missing n8n URL.
6) Forward synchronously to merchant n8n with headers and inline retry (5xx/408/429/network).
7) Store response, attempts, status for retries and debugging.

## Scheduled Retry
Command: `webhooks:retry-failed`

Scheduler (already registered): every two minutes.

Cron example:
```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Filament Admin
- MerchantResource: CRUD merchant mapping; action “Ping n8n” updates `last_ping_ok_at`.
- WebhookEventResource: list/filter, view raw headers/payload/response; action “Retry Now”.

## n8n Forwarding
URL = `rtrim(n8n_base_url,'/') . '/' . ltrim(n8n_path,'/')`

Headers:
- `Content-Type: application/json`
- `X-Forwarded-By: n8n-ai-salla-proxy`
- `X-Salla-Event`
- `X-Salla-Event-Id`
- `X-Salla-Merchant-Id`
- `X-Event-Checksum: sha256(normalized_payload)`

Auth: bearer/basic/none per merchant.

## Testing
```
php artisan test
```

Fixtures:
- `tests/Fixtures/salla/order.created.json`
- `tests/Fixtures/salla/customer.created.json`

Local cURL example:
```
RAW=$(cat tests/Fixtures/salla/order.created.json)
SIG=$(php -r 'echo base64_encode(hash_hmac("sha256", file_get_contents("php://stdin"), getenv("SALLA_WEBHOOK_SECRET"), true));' <<< "$RAW")
curl -sS -X POST http://localhost:8000/webhooks/salla \
  -H "Content-Type: application/json" \
  -H "X-Salla-Signature: $SIG" \
  --data "$RAW"
```

## Extending Supported Events
Add strings to `config('salla.supported_events')`. For different merchant id paths per model, define `paths_overrides` like:

```
'paths_overrides' => [
  'product.*' => ['merchant_id' => 'data.store.id'],
]
```

## Development Runbook

### Quick Start

1. **Install dependencies**
   ```bash
   make install
   # Or manually:
   composer install
   npm install
   php artisan key:generate
   ```

2. **Configure environment**
   - Copy `.env.example` to `.env`
   - Update database credentials
   - Set `ACTIONS_API_BEARER` to a strong random token
   - Update Salla app credentials if you have them

3. **Setup database**
   ```bash
   make migrate
   make seed  # Creates demo merchant with ID 112233
   ```

4. **Start development server**
   ```bash
   make serve
   # Or: php artisan serve
   ```

### Testing OAuth Token Capture

When Salla sends the `app.store.authorize` event to your app:

```bash
curl -X POST http://localhost:8000/api/app-events/authorized \
  -H "Content-Type: application/json" \
  -d '{
    "event": "app.store.authorize",
    "data": {
      "store": {
        "id": "112233",
        "name": "Test Store"
      },
      "tokens": {
        "access_token": "salla_access_token_123",
        "refresh_token": "salla_refresh_token_456",
        "expires_in": 7200
      }
    }
  }'
```

### Testing Actions API

All actions require the Bearer token from `ACTIONS_API_BEARER`.

#### Create Product
```bash
curl -X POST http://localhost:8000/api/actions/products/create \
  -H "Authorization: Bearer change_me_strong_random_token" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "payload": {
      "name": "New Product",
      "sku": "SKU-001",
      "price": 199.00,
      "quantity": 10,
      "description": "Product description"
    }
  }'
```

#### List Orders
```bash
curl -X GET "http://localhost:8000/api/actions/orders/list?merchant_id=112233&page=1&per_page=20" \
  -H "Authorization: Bearer change_me_strong_random_token"
```

#### Get Single Customer
```bash
curl -X GET "http://localhost:8000/api/actions/customers/get?merchant_id=112233&customer_id=456789" \
  -H "Authorization: Bearer change_me_strong_random_token"
```

#### Update Order Status
```bash
curl -X PATCH http://localhost:8000/api/actions/orders/update \
  -H "Authorization: Bearer change_me_strong_random_token" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "order_id": "987654",
    "payload": {
      "status": "shipped"
    }
  }'
```

#### Create Export Job
```bash
curl -X POST http://localhost:8000/api/actions/exports/create \
  -H "Authorization: Bearer change_me_strong_random_token" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "payload": {
      "type": "orders",
      "format": "csv"
    }
  }'
```

### n8n HTTP Request Node Configuration

In n8n, use the HTTP Request node with these settings:

**Authentication**: None (we handle auth via header)

**Headers**:
```json
{
  "Authorization": "Bearer {{$env.ACTIONS_API_BEARER}}",
  "Content-Type": "application/json"
}
```

**Example: Create Product in n8n**
- Method: `POST`
- URL: `{{$env.N8NPROXY_URL}}/api/actions/products/create`
- Body (JSON):
  ```json
  {
    "merchant_id": "{{$json.merchant_id}}",
    "payload": {
      "name": "{{$json.product_name}}",
      "sku": "{{$json.sku}}",
      "price": {{$json.price}},
      "quantity": {{$json.quantity}}
    }
  }
  ```

### Database Schema

#### New Tables
- `merchant_tokens` - OAuth tokens per merchant
- `salla_action_audits` - Audit log for all API actions

#### Existing Tables
- `merchants` - Merchant configuration
- `webhook_events` - Webhook delivery tracking

### Monitoring & Debugging

1. **Check action audit logs**
   ```sql
   SELECT * FROM salla_action_audits 
   WHERE merchant_id = '...' 
   ORDER BY created_at DESC;
   ```

2. **View token status**
   ```sql
   SELECT salla_merchant_id, access_token_expires_at 
   FROM merchant_tokens;
   ```

3. **Failed requests (non-2xx responses)**
   ```sql
   SELECT * FROM salla_action_audits 
   WHERE status_code >= 400 
   ORDER BY created_at DESC;
   ```

### Production Deployment

1. Set strong values for:
   - `ACTIONS_API_BEARER`
   - `SALLA_CLIENT_SECRET`
   - `SALLA_WEBHOOK_SECRET`

2. Configure proper Salla OAuth credentials

3. Enable HTTPS for production

4. Set up monitoring for:
   - Failed API calls (status_code >= 400)
   - Token expiration warnings
   - Webhook delivery failures

5. Configure rate limiting if needed:
   - Add to routes: `->middleware('throttle:60,1')`

### Troubleshooting

**401 Unauthorized on Actions API**
- Check Bearer token matches `ACTIONS_API_BEARER` in .env
- Ensure "Bearer " prefix is included

**"No OAuth token found for merchant"**
- Merchant needs to authorize the app first
- Check `merchant_tokens` table

**Token expired errors**
- Check if refresh token is valid
- Verify OAuth credentials are correct
- Check `access_token_expires_at` in database

**Salla API errors**
- Check `salla_action_audits` table for details
- Verify API endpoints in `config/salla_api.php`
- Check Salla API documentation for payload requirements
