# N8NProxy - Salla Integration Platform

A Laravel 11 proxy platform that manages Salla webhooks and provides action endpoints for n8n workflows. This platform exists because Salla App settings allow only a single static webhook URL; we proxy & route dynamically to each merchant's n8n, and we expose action endpoints that n8n can call to operate on the merchant's Salla store.

## Features

### ✅ Triggers (Webhooks)
- Ingests Salla webhooks and routes them to each merchant's n8n URL
- Supports all Salla webhook events (orders, products, customers, etc.)
- Configurable authentication (none, bearer, basic) for n8n endpoints

### ✅ Actions (Salla Admin API)
- **Orders**: create, delete, get, list, update
- **Products**: create, delete, get, list, update
- **Customers**: delete, get, list, update
- **Marketing/Coupons**: create, delete, get, list, update
- **Categories**: create, delete, get, list, update
- **Exports**: create, list, status, download

### ✅ OAuth Management
- Automatic token capture from Salla app events
- Auto-refresh of expired tokens
- Secure encrypted storage of access/refresh tokens

### ✅ Enterprise Features
- Complete audit trail of all API calls
- Rate limiting and input validation
- Comprehensive error handling and retries
- Bearer token authentication for actions API

## Quick Start

### 1. Environment Setup

Copy the environment file and update with your Salla app credentials:

```bash
cp .env.example .env
```

Update these required variables in `.env`:

```env
# N8N Proxy Configuration
APP_NAME=N8NProxy
APP_URL=http://127.0.0.1:8000

# Salla App keys (from your Salla Partner dashboard)
SALLA_CLIENT_ID=your_salla_client_id
SALLA_CLIENT_SECRET=your_salla_client_secret
SALLA_WEBHOOK_SECRET=your_webhook_secret

# Actions API protection (generate a strong random token)
ACTIONS_API_BEARER=your_strong_random_token_here
```

### 2. Installation & Database Setup

```bash
# Install dependencies
make dev-install

# Generate application key
make key

# Run migrations
make migrate

# Seed demo merchants for testing
make seed
```

### 3. Start the Server

```bash
# Start development server
make serve
```

The application will be available at `http://localhost:8000`

## Usage

### Setting up Merchants

Merchants are automatically created when they authorize your Salla app or when you create them manually via the Filament admin panel at `/admin`.

Each merchant needs:
- `salla_merchant_id`: The store ID from Salla
- `n8n_base_url`: Base URL of their n8n instance
- `n8n_path`: Webhook path (default: `/webhook/salla`)
- `n8n_auth_type`: Authentication method (`none`, `bearer`, or `basic`)

### OAuth Token Capture

Tokens are automatically captured when merchants authorize your app. Set up these webhook endpoints in your Salla app:

- **App Authorized**: `POST /api/app-events/authorized`
- **App Installed**: `POST /api/app-events/installed`

### Actions API Usage

All action endpoints require Bearer authentication:

```bash
curl -H "Authorization: Bearer YOUR_ACTIONS_API_BEARER" \
     "http://localhost:8000/api/actions/..."
```

#### Orders

```bash
# List orders
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/orders/list?merchant_id=demo_merchant_123&page=1&per_page=20"

# Get specific order
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/orders/get?merchant_id=demo_merchant_123&order_id=12345"

# Create order
curl -X POST -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","payload":{"customer_id":456,"items":[{"product_id":789,"quantity":1}]}}' \
     "http://localhost:8000/api/actions/orders/create"

# Update order
curl -X PATCH -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","order_id":"12345","payload":{"status":"completed"}}' \
     "http://localhost:8000/api/actions/orders/update"

# Delete order
curl -X DELETE -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","order_id":"12345"}' \
     "http://localhost:8000/api/actions/orders/delete"
```

#### Products

```bash
# List products
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/products/list?merchant_id=demo_merchant_123&page=1"

# Create product
curl -X POST -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","payload":{"name":"New Product","sku":"SKU-001","price":199.00,"quantity":10}}' \
     "http://localhost:8000/api/actions/products/create"
```

#### Customers

```bash
# List customers
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/customers/list?merchant_id=demo_merchant_123"

# Get customer
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/customers/get?merchant_id=demo_merchant_123&customer_id=12345"
```

#### Marketing/Coupons

```bash
# List coupons
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/marketing/coupons/list?merchant_id=demo_merchant_123"

# Create coupon
curl -X POST -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","payload":{"code":"SAVE20","type":"percentage","value":20}}' \
     "http://localhost:8000/api/actions/marketing/coupons/create"
```

#### Categories

```bash
# List categories
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/categories/list?merchant_id=demo_merchant_123"

# Create category
curl -X POST -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","payload":{"name":"Electronics","description":"Electronic products"}}' \
     "http://localhost:8000/api/actions/categories/create"
```

#### Exports

```bash
# Create export job
curl -X POST -H "Authorization: Bearer $ACTIONS_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"merchant_id":"demo_merchant_123","payload":{"type":"orders","format":"csv"}}' \
     "http://localhost:8000/api/actions/exports/create"

# Check export status
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/exports/status?merchant_id=demo_merchant_123&export_id=12345"

# Download export
curl -H "Authorization: Bearer $ACTIONS_TOKEN" \
     "http://localhost:8000/api/actions/exports/download?merchant_id=demo_merchant_123&export_id=12345"
```

## N8N Integration

### HTTP Request Node Configuration

For all action calls from n8n, use these settings:

**Authentication:**
- Type: `Generic Credential Type`
- Generic Auth Type: `Header Auth`
- Name: `Authorization`
- Value: `Bearer YOUR_ACTIONS_API_BEARER`

**Example: Create Product Node**

```json
{
  "method": "POST",
  "url": "{{$env.N8N_PROXY_URL}}/api/actions/products/create",
  "headers": {
    "Authorization": "Bearer {{$env.ACTIONS_API_BEARER}}"
  },
  "body": {
    "merchant_id": "{{$json.merchant_id}}",
    "payload": {
      "name": "{{$json.product_name}}",
      "sku": "{{$json.sku}}",
      "price": "{{$json.price}}",
      "quantity": "{{$json.quantity}}"
    }
  }
}
```

**Example: List Orders Node**

```json
{
  "method": "GET",
  "url": "{{$env.N8N_PROXY_URL}}/api/actions/orders/list?merchant_id={{$json.merchant_id}}&page=1&per_page=20&status=pending"
}
```

## Development

### Available Commands

```bash
make help          # Show all available commands
make serve         # Start development server
make test          # Run tests
make seed          # Seed demo data
make fresh         # Fresh migration with seeding
make pint          # Format code
make demo-curl     # Show example curl commands
```

### Running Tests

```bash
# Run all tests
make test

# Run specific test file
cd app && vendor/bin/pest tests/Feature/SallaActionsApiTest.php

# Run with coverage
cd app && vendor/bin/pest --coverage
```

### Demo Data

The seeder creates two demo merchants:

- **demo_merchant_123**: Valid token for 2 hours
- **demo_merchant_456**: Token expiring in 30 minutes (for testing refresh)

### Debugging

- **Application logs**: `make logs`
- **Action audits**: Check `salla_action_audits` table
- **Token status**: Check `merchant_tokens` table
- **Admin panel**: Visit `/admin` for merchant management

## API Reference

### Authentication

All `/api/actions/*` endpoints require Bearer token authentication:

```
Authorization: Bearer YOUR_ACTIONS_API_BEARER
```

### Common Parameters

- `merchant_id`: Salla store ID (required for all actions)
- `payload`: Request body for create/update operations
- `{resource}_id`: Resource identifier for get/update/delete operations
- Query parameters: `page`, `per_page`, `search`, etc.

### Response Format

All endpoints return the raw Salla API response with the original status code:

```json
{
  "data": {...},
  "status": 200,
  "pagination": {...}
}
```

### Rate Limiting

Actions API endpoints are rate limited to 60 requests per minute per IP.

## Architecture

### Key Components

- **SallaHttpClient**: Handles authenticated API calls with auto-refresh
- **OAuthTokenStore**: Manages token storage and retrieval
- **OAuthRefresher**: Handles token refresh logic
- **ActionControllers**: Route handlers for each resource type
- **ActionsApiAuth**: Bearer token authentication middleware

### Database Tables

- `merchants`: Store configuration for each Salla merchant
- `merchant_tokens`: OAuth tokens with encrypted storage
- `salla_action_audits`: Complete audit trail of API calls
- `webhook_events`: Webhook event storage and forwarding logs

### Security Features

- Encrypted token storage
- Bearer token authentication
- Input validation and sanitization
- Rate limiting
- Comprehensive audit logging

## Deployment

### Production Setup

1. Set `APP_ENV=production` in `.env`
2. Generate strong random `ACTIONS_API_BEARER` token
3. Configure proper database (MySQL/PostgreSQL)
4. Set up queue worker: `php artisan queue:work`
5. Configure web server (nginx/Apache) to serve `public/`

### Required Environment Variables

```env
APP_NAME=N8NProxy
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=n8nproxy
DB_USERNAME=username
DB_PASSWORD=password

SALLA_CLIENT_ID=your_salla_client_id
SALLA_CLIENT_SECRET=your_salla_client_secret
SALLA_WEBHOOK_SECRET=your_webhook_secret
ACTIONS_API_BEARER=your_production_bearer_token
```

## Troubleshooting

### Common Issues

**401 Unauthorized on Actions API**
- Verify `ACTIONS_API_BEARER` is set correctly
- Check Authorization header format: `Bearer token_here`

**Token Refresh Failures**
- Verify Salla OAuth credentials are correct
- Check token hasn't been revoked in Salla dashboard
- Ensure merchant has valid refresh token

**Webhook Not Forwarding**
- Verify merchant n8n URL is accessible
- Check merchant `is_active` status
- Review webhook event logs in admin panel

### Logs and Monitoring

- Application logs: `storage/logs/laravel.log`
- Action audits: `salla_action_audits` table
- Webhook events: `webhook_events` table
- Queue jobs: `jobs` and `failed_jobs` tables

## Support

For issues and questions:

1. Check the troubleshooting section above
2. Review application logs and audit tables
3. Test with demo merchants using `make seed`
4. Verify Salla app configuration and credentials

---

**Built with Laravel 11, PHP 8.2+, and ❤️**