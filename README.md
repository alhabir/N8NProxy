# N8NProxy – Salla Triggers & Actions

This Laravel 11 app proxies Salla webhooks to each merchant's n8n and exposes a uniform Actions API that calls Salla Admin API on behalf of merchants (orders, products, customers, coupons, categories, exports). It stores OAuth tokens per merchant, auto-refreshes, audits all calls, and protects the Actions API via bearer.

## Environment (.env additions)

```
APP_NAME=N8NProxy
APP_URL=http://127.0.0.1:8000

SALLA_CLIENT_ID=a5500786-2c22-4ca2-bab9-4cc6e0cd4906
SALLA_CLIENT_SECRET=f9b1a18cec45ac342bb9cf7bfd45ac73
SALLA_WEBHOOK_SECRET=519dd95fbd631b78020de2e36ae116c3
SALLA_API_BASE=https://api.salla.dev/admin/v2
SALLA_OAUTH_TOKEN_URL=https://accounts.salla.sa/oauth2/token

FORWARD_DEFAULT_TIMEOUT_MS=6000
FORWARD_SYNC_RETRIES=2
FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
ALLOW_TEST_MODE=true

ACTIONS_API_BEARER=change_me_strong_random_token
```

## Config
- `config/salla.php` – trigger settings (already present)
- `config/salla_api.php` – Admin API endpoints (URL templates)

## Database
- `merchants` (existing)
- `merchant_tokens` – per-merchant access/refresh tokens
- `salla_action_audits` – all action invocations

Run migrations:
```
php artisan migrate
```

## Services
- `App\Services\Salla\OAuthTokenStore` – persist and read tokens
- `App\Services\Salla\OAuthRefresher` – refresh via OAuth2 refresh_token
- `App\Services\Salla\SallaHttpClient` – inject token, auto-refresh on 401, audit

## Actions API (Bearer required)
Set header: `Authorization: Bearer ${ACTIONS_API_BEARER}`

Base path: `${APP_URL}/api/actions`

Examples:
- Orders: POST `orders/create`, DELETE `orders/delete`, GET `orders/get`, GET `orders/list`, PATCH `orders/update`
- Products: POST `products/create`, DELETE `products/delete`, GET `products/get`, GET `products/list`, PATCH `products/update`
- Customers: DELETE `customers/delete`, GET `customers/get`, GET `customers/list`, PATCH `customers/update`
- Marketing/Coupons: POST `marketing/coupons/create`, DELETE `marketing/coupons/delete`, GET `marketing/coupons/get`, GET `marketing/coupons/list`, PATCH `marketing/coupons/update`
- Categories: POST `categories/create`, DELETE `categories/delete`, GET `categories/get`, GET `categories/list`, PATCH `categories/update`
- Exports: POST `exports/create`, GET `exports/list`, GET `exports/status`, GET `exports/download`

## Salla App Events (Easy Mode OAuth)
- `POST /api/app-events/authorized` – capture access/refresh tokens
- `POST /api/app-events/installed` – optional bookkeeping

## n8n Usage (Actions)
- Create Product (HTTP Request node):
  - Method: POST
  - URL: `${APP_URL}/api/actions/products/create`
  - Headers: `Authorization: Bearer ${ACTIONS_API_BEARER}`
  - JSON:
    ```json
    {
      "merchant_id": "112233",
      "payload": {"name":"New Product","sku":"SKU-001","price":199.00,"quantity":10}
    }
    ```
- List Orders:
  - GET `${APP_URL}/api/actions/orders/list?merchant_id=112233&page=1&per_page=20`

## Runbook

### Run locally
```
cp .env.example .env
php artisan key:generate
composer install
php artisan migrate
php artisan db:seed --class=DemoMerchantSeeder
php artisan serve
```

### Seed a demo merchant
```
php artisan db:seed --class=DemoMerchantSeeder
```

### Tests
```
php artisan test
```

### Sample curl
```
# Products: create
curl -sS -X POST "$APP_URL/api/actions/products/create" \
 -H "Authorization: Bearer $ACTIONS_API_BEARER" \
 -H "Content-Type: application/json" \
 -d '{"merchant_id":"112233","payload":{"name":"New Product","sku":"SKU-001","price":199,"quantity":10}}'

# Orders: list
curl -sS "$APP_URL/api/actions/orders/list?merchant_id=112233&page=1&per_page=20" \
 -H "Authorization: Bearer $ACTIONS_API_BEARER"
```

### Notes
- Webhook triggers remain unchanged: `POST /webhooks/salla`.
- All actions are audited in `salla_action_audits` (status, timing, payload/query truncated to 64KB).
