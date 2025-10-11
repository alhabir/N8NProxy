# Live Salla App Events — Verification

## Nginx (POSTs to /webhooks/salla/app-events)
```
18.157.139.141 - - [11/Oct/2025:13:28:40 +0000] "POST /webhooks/salla/app-events HTTP/1.1" 401 47 "-" "Salla Webhook v1.0"
18.157.139.141 - - [11/Oct/2025:13:35:43 +0000] "POST /webhooks/salla/app-events HTTP/1.1" 401 47 "-" "Salla Webhook v1.0"
34.28.46.125 - - [11/Oct/2025:13:34:50 +0000] "POST /webhooks/salla/app-events HTTP/1.1" 200 59 "-" "curl/8.12.1"
```

## Laravel Log (app.* lines)
```
[2025-10-11 16:34:50] production.INFO: Salla webhook received {"event":"app.installed","event_id":"synthetic-check","merchant_reference":"999000111","is_app_event":true}
[2025-10-11 16:34:50] production.INFO: Salla app event stored {"event_id":"58abf5c4-1d3a-498c-ae16-28083e15a481","event_name":"app.installed","salla_merchant_id":"999000111","merchant_id":"dbfa9617-abc6-4837-adf2-500b9da55543"}
[2025-10-11 16:39:29] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false}
```

## DB Snapshot
```
count:5
app.installed|999000111|2025-10-11 16:34:50
app.installed|999000111|2025-10-11 16:29:05
app.installed|123456789|2025-10-11 16:04:50
app.uninstalled|999001|2025-10-11 14:13:33
app.installed|999001|2025-10-11 14:13:33
```

## Synthetic Check
```
HTTP 200 + {"ok":true,"kind":"app","event":"app.installed"}
```

Notes: Real Salla webhook attempts (IP 18.157.x.x) are reaching nginx but returning 401 invalid_token, indicating the token configured in Salla does not match the server-side secret (expecting 519dd95fbd631b78020de2e36ae116c3). Once the token is updated in Salla, app.install / app.uninstalled events should persist as shown above.