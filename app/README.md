# n8n ai – Salla Webhook Proxy

This Laravel 11 app receives Salla webhooks, validates signatures, stores deliveries for idempotency/retry/debugging, and forwards payloads to each merchant’s n8n webhook URL. Phase 1 supports Order and Customer events; new events are added by editing `config/salla.php` only.

## Requirements
- PHP 8.2+
- Laravel 11+
- MySQL (SQLite works locally)

## Environment
Add to `.env`:

```
APP_URL=http://localhost:8000
SALLA_WEBHOOK_SECRET=changeme
FORWARD_DEFAULT_TIMEOUT_MS=6000
FORWARD_SYNC_RETRIES=2
FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
ALLOW_TEST_MODE=true
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
- `POST /webhooks/salla` — main ingest
- `POST /webhooks/test` — dev-only mock (requires `ALLOW_TEST_MODE=true`)
- `GET /health` — status JSON

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
