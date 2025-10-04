# n8n ai – Salla Webhook Proxy

This Laravel 11 app receives Salla webhooks, validates signatures, stores deliveries for idempotency/retry/debugging, and forwards payloads to each merchant’s n8n webhook URL. Phase 1 supports Order and Customer events; new events are added by editing `config/salla.php` only.

## Requirements
- PHP 8.2+
- Laravel 11+
- MySQL (SQLite works locally)

## Environment

Copy `.env.example` to `.env` and configure:

```bash
# Application
APP_NAME=N8NProxy
APP_URL=http://127.0.0.1:8000

# Salla App Credentials (from Salla Partner Dashboard)
SALLA_CLIENT_ID=your-client-id
SALLA_CLIENT_SECRET=your-client-secret
SALLA_WEBHOOK_SECRET=your-webhook-secret

# Salla API Configuration
SALLA_API_BASE=https://api.salla.dev/admin/v2
SALLA_OAUTH_TOKEN_URL=https://accounts.salla.sa/oauth2/token

# Webhook Forwarding
FORWARD_DEFAULT_TIMEOUT_MS=6000
FORWARD_SYNC_RETRIES=2
FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
ALLOW_TEST_MODE=true

# Actions API Protection
ACTIONS_API_BEARER=your-strong-random-token-here
```

Setup:

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed demo merchant (optional)
php artisan db:seed --class=DemoMerchantSeeder
```

## Config
`config/salla.php`:
- `signature_header`: `X-Salla-Signature`
- dot-paths: `merchant_id = data.store.id`, `event_name = event`, `event_id = id`
- `supported_events`: Order + Customer; append strings to extend
- optional `paths_overrides` per event prefix (e.g., `product.*`)

## Routes

### Webhooks (Triggers)
- `POST /webhooks/salla` — main webhook ingest
- `POST /webhooks/test` — dev-only mock (requires `ALLOW_TEST_MODE=true`)
- `GET /health` — status JSON

### Salla App Events (OAuth)
- `POST /api/app-events/authorized` — captures OAuth tokens from Salla
- `POST /api/app-events/installed` — tracks app installations

### Actions API (Protected by Bearer token)
All endpoints require `Authorization: Bearer {ACTIONS_API_BEARER}` header.

**Orders**
- `POST /api/actions/orders/create`
- `GET /api/actions/orders/get`
- `GET /api/actions/orders/list`
- `PATCH /api/actions/orders/update`
- `DELETE /api/actions/orders/delete`

**Products**
- `POST /api/actions/products/create`
- `GET /api/actions/products/get`
- `GET /api/actions/products/list`
- `PATCH /api/actions/products/update`
- `DELETE /api/actions/products/delete`

**Customers**
- `GET /api/actions/customers/get`
- `GET /api/actions/customers/list`
- `PATCH /api/actions/customers/update`
- `DELETE /api/actions/customers/delete`

**Marketing / Coupons**
- `POST /api/actions/marketing/coupons/create`
- `GET /api/actions/marketing/coupons/get`
- `GET /api/actions/marketing/coupons/list`
- `PATCH /api/actions/marketing/coupons/update`
- `DELETE /api/actions/marketing/coupons/delete`

**Categories**
- `POST /api/actions/categories/create`
- `GET /api/actions/categories/get`
- `GET /api/actions/categories/list`
- `PATCH /api/actions/categories/update`
- `DELETE /api/actions/categories/delete`

**Exports**
- `POST /api/actions/exports/create`
- `GET /api/actions/exports/list`
- `GET /api/actions/exports/status`
- `GET /api/actions/exports/download`

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

```php
'paths_overrides' => [
  'product.*' => ['merchant_id' => 'data.store.id'],
]
```

---

## Actions API Usage Guide

### OAuth Token Management

The platform automatically captures and manages OAuth tokens when merchants install/authorize your Salla app. Configure your Salla App to send these events to:
- `{APP_URL}/api/app-events/authorized`
- `{APP_URL}/api/app-events/installed`

Tokens are:
- Stored encrypted in the database
- Automatically refreshed when expiring (within 60 seconds)
- Retried on 401 responses

### Making Action Calls from n8n

Use HTTP Request nodes in your n8n workflows:

#### Example 1: Create a Product

**HTTP Request Node Configuration:**
```
Method: POST
URL: http://127.0.0.1:8000/api/actions/products/create
Authentication: None (use headers instead)

Headers:
  Authorization: Bearer your-strong-random-token-here
  Content-Type: application/json

Body (JSON):
{
  "merchant_id": "112233",
  "payload": {
    "name": "New Product",
    "sku": "SKU-001",
    "price": 199.00,
    "quantity": 10,
    "description": "Product description"
  }
}
```

#### Example 2: List Orders

**HTTP Request Node Configuration:**
```
Method: GET
URL: http://127.0.0.1:8000/api/actions/orders/list
Authentication: None (use headers instead)

Headers:
  Authorization: Bearer your-strong-random-token-here

Query Parameters:
  merchant_id: 112233
  page: 1
  per_page: 20
  status: pending
```

#### Example 3: Update Customer

**HTTP Request Node Configuration:**
```
Method: PATCH
URL: http://127.0.0.1:8000/api/actions/customers/update
Authentication: None (use headers instead)

Headers:
  Authorization: Bearer your-strong-random-token-here
  Content-Type: application/json

Body (JSON):
{
  "merchant_id": "112233",
  "customer_id": "456789",
  "payload": {
    "email": "updated@example.com",
    "mobile": "+966501234567"
  }
}
```

#### Example 4: Create Export Job

**HTTP Request Node Configuration:**
```
Method: POST
URL: http://127.0.0.1:8000/api/actions/exports/create
Authentication: None (use headers instead)

Headers:
  Authorization: Bearer your-strong-random-token-here
  Content-Type: application/json

Body (JSON):
{
  "merchant_id": "112233",
  "payload": {
    "type": "orders",
    "format": "csv"
  }
}
```

### Testing Actions with cURL

```bash
# List orders
curl -X GET "http://127.0.0.1:8000/api/actions/orders/list?merchant_id=112233&page=1" \
  -H "Authorization: Bearer your-strong-random-token-here"

# Create product
curl -X POST http://127.0.0.1:8000/api/actions/products/create \
  -H "Authorization: Bearer your-strong-random-token-here" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "payload": {
      "name": "Test Product",
      "price": 99.99,
      "quantity": 5
    }
  }'

# Get single order
curl -X GET "http://127.0.0.1:8000/api/actions/orders/get?merchant_id=112233&order_id=789" \
  -H "Authorization: Bearer your-strong-random-token-here"

# Update order
curl -X PATCH http://127.0.0.1:8000/api/actions/orders/update \
  -H "Authorization: Bearer your-strong-random-token-here" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "order_id": "789",
    "payload": {
      "status": "shipped"
    }
  }'

# Delete order
curl -X DELETE http://127.0.0.1:8000/api/actions/orders/delete \
  -H "Authorization: Bearer your-strong-random-token-here" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "order_id": "789"
  }'

# Create coupon
curl -X POST http://127.0.0.1:8000/api/actions/marketing/coupons/create \
  -H "Authorization: Bearer your-strong-random-token-here" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "112233",
    "payload": {
      "code": "SAVE20",
      "type": "percentage",
      "amount": 20
    }
  }'
```

### Auditing & Monitoring

All action requests are automatically logged to the `salla_action_audits` table with:
- Request metadata (sanitized)
- Response status and body (truncated to 64KB)
- Duration in milliseconds
- Resource and action type

View audits in the Filament admin panel or query directly:

```php
use App\Models\SallaActionAudit;

// Recent failed requests
SallaActionAudit::where('status_code', '>=', 400)
    ->orderBy('created_at', 'desc')
    ->get();

// Slow requests
SallaActionAudit::where('duration_ms', '>', 2000)
    ->orderBy('duration_ms', 'desc')
    ->get();
```

### Security Notes

1. **Bearer Token**: Keep `ACTIONS_API_BEARER` secret and rotate periodically
2. **Rate Limiting**: Actions API is throttled to 60 requests per minute per IP
3. **Token Encryption**: OAuth tokens are encrypted at rest using Laravel's encryption
4. **HTTPS**: Use HTTPS in production to protect tokens in transit
5. **Scopes**: Ensure your Salla App has appropriate OAuth scopes for the actions you need

### Troubleshooting

**401 Unauthorized on action calls:**
- Check that `Authorization` header includes correct bearer token
- Verify `ACTIONS_API_BEARER` in `.env` matches your request

**No tokens found for merchant:**
- Ensure merchant has authorized the app via Salla
- Check that `/api/app-events/authorized` was called and tokens stored
- Verify merchant exists and is active in Filament admin

**Token refresh failures:**
- Check `SALLA_CLIENT_ID` and `SALLA_CLIENT_SECRET` are correct
- Ensure refresh token hasn't been revoked in Salla
- Check logs for detailed error messages

**Rate limiting:**
- Wait before retrying (60 requests per minute limit)
- Implement exponential backoff in n8n workflows
- Contact support to increase limits if needed

---

## Running Locally

```bash
# Start development server
php artisan serve

# In separate terminal: run queue worker
php artisan queue:work

# In separate terminal: run scheduler (for webhook retries)
php artisan schedule:work

# Or use Laravel Sail (Docker)
./vendor/bin/sail up
```

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Run `php artisan config:cache` and `php artisan route:cache`
3. Use MySQL/PostgreSQL instead of SQLite
4. Set up supervisor for queue workers
5. Configure cron for scheduler
6. Use HTTPS with valid SSL certificate
7. Set strong `ACTIONS_API_BEARER` token
8. Enable rate limiting at nginx/load balancer level

## Database Schema

**merchants**: Store information, n8n webhook URL, auth
**merchant_tokens**: Encrypted OAuth tokens per merchant
**webhook_events**: Incoming Salla webhooks (idempotency, retry)
**salla_action_audits**: Outbound API calls audit trail

## Architecture

```
Salla → Webhooks → N8NProxy → n8n (Triggers)
n8n → Actions API → N8NProxy → Salla API (Actions)
```

The platform acts as a bidirectional proxy:
- **Inbound**: Routes Salla events to merchant-specific n8n webhooks
- **Outbound**: Provides authenticated API to call Salla on behalf of merchants
