# N8NProxy SiteGround Deployment Runbook

## Overview
This runbook provides complete instructions for deploying the N8NProxy application on SiteGround hosting. The application acts as a bridge between Salla stores and n8n instances, providing webhook forwarding and API actions.

## Prerequisites
- SiteGround hosting account with PHP 8.2+
- MySQL database access
- SSL certificate (for production)
- Domain/subdomain configured (e.g., app.n8ndesigner.com)

## Deployment Steps

### 1. Database Setup
1. **Log into SiteGround cPanel**
2. **Navigate to MySQL Databases**
3. **Create a new database:**
   - Database name: `n8nproxy_prod` (or your preferred name)
   - Note down the full database name (usually includes your username prefix)
4. **Create a database user:**
   - Username: `n8nproxy_user` (or your preferred name)
   - Password: Generate a strong password
   - Grant all privileges to the database
5. **Note down the credentials:**
   - Database name: `username_n8nproxy_prod`
   - Username: `username_n8nproxy_user`
   - Password: `your_strong_password`
   - Host: `localhost` (usually)

### 2. File Upload
1. **Extract the deployment package:**
   ```bash
   unzip siteground-n8nproxy.zip
   ```
2. **Upload all files to your subdomain directory:**
   - Upload to: `/public_html/app/` (or your subdomain directory)
   - Ensure all files are uploaded with correct permissions
3. **Set file permissions:**
   ```bash
   chmod 755 storage/
   chmod 755 bootstrap/cache/
   chmod 644 .env
   ```

### 3. Environment Configuration
1. **Copy the environment template:**
   ```bash
   cp env.example.siteground .env
   ```
2. **Update the .env file with your values:**
   ```env
   APP_NAME=N8NProxy
   APP_ENV=production
   APP_KEY=base64:YOUR_GENERATED_KEY_HERE
   APP_DEBUG=false
   APP_URL=https://app.n8ndesigner.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=username_n8nproxy_prod
   DB_USERNAME=username_n8nproxy_user
   DB_PASSWORD=your_strong_password

   SALLA_CLIENT_ID=a5500786-2c22-4ca2-bab9-4cc6e0cd4906
   SALLA_CLIENT_SECRET=f9b1a18cec45ac342bb9cf7bfd45ac73
   SALLA_WEBHOOK_SECRET=519dd95fbd631b78020de2e36ae116c3
   SALLA_API_BASE=https://api.salla.dev/admin/v2
   SALLA_OAUTH_TOKEN_URL=https://accounts.salla.sa/oauth2/token

   ACTIONS_API_BEARER=your_very_strong_random_token_here

   FORWARD_DEFAULT_TIMEOUT_MS=6000
   FORWARD_SYNC_RETRIES=2
   FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
   ALLOW_TEST_MODE=false
   ```

### 4. Application Setup
1. **Generate application key:**
   ```bash
   php artisan key:generate
   ```
2. **Run database migrations:**
   ```bash
   php artisan migrate --force
   ```
3. **Create admin user:**
   ```bash
   php artisan tinker
   ```
   Then run:
   ```php
   $user = new App\Models\User();
   $user->name = 'Admin';
   $user->email = 'admin@yourdomain.com';
   $user->password = bcrypt('your_admin_password');
   $user->is_admin = true;
   $user->save();
   exit
   ```

### 5. Cron Job Setup
1. **Go to SiteGround cPanel → Cron Jobs**
2. **Add a new cron job:**
   - Command: `/usr/local/bin/ea-php82 /home/USERNAME/public_html/app/artisan schedule:run >> /home/USERNAME/laravel-scheduler.log 2>&1`
   - Schedule: `* * * * *` (every minute)
   - Replace `USERNAME` with your SiteGround username
3. **Verify cron is working:**
   - Check the log file: `/home/USERNAME/laravel-scheduler.log`
   - Look for successful execution messages

### 6. Salla Console Configuration
Configure your Salla app with these **exact** settings:

#### Webhook Configuration
- **Webhook URL:** `https://app.n8ndesigner.com/webhooks/salla`
- **Security Strategy:** Signature
- **Webhook Secret Key:** `519dd95fbd631b78020de2e36ae116c3`

#### App Events Configuration
- **App Authorized Event URL:** `https://app.n8ndesigner.com/app-events/authorized`
- **Purpose:** Captures OAuth tokens for API access

#### Required Scopes
Enable these scopes in your Salla app:
- ✅ Orders: Read/Write
- ✅ Products: Read/Write
- ✅ Customers: Read/Write
- ✅ Marketing: Read/Write
- ✅ Categories: Read/Write
- ✅ Exports: Read/Write
- ✅ Webhooks: Read/Write

#### Store Events
Enable these events (you can add more later):
- ✅ Order Created
- ✅ Order Updated
- ✅ Order Cancelled
- ✅ Customer Created
- ✅ Customer Updated

### 7. Testing the Deployment

#### 7.1 Basic Application Test
1. **Visit your domain:** `https://app.n8ndesigner.com`
2. **Verify the application loads without errors**
3. **Check the admin dashboard:** `https://app.n8ndesigner.com/admin`
4. **Log in with your admin credentials**

#### 7.2 Webhook Endpoint Test
Test the webhook endpoint with a sample payload:
```bash
curl -X POST https://app.n8ndesigner.com/webhooks/salla \
  -H "Content-Type: application/json" \
  -H "X-Salla-Signature: your_signature_here" \
  -d '{
    "event": "order.created",
    "id": "test_123",
    "data": {
      "store": {
        "id": "test_store_123"
      }
    }
  }'
```

#### 7.3 Salla App Installation Test
1. **Install the "n8n ai" app in a test Salla store**
2. **Verify the app authorization event is received**
3. **Check the admin panel for the new merchant**
4. **Approve the merchant account**

#### 7.4 n8n Integration Test
1. **Set up a test n8n instance**
2. **Create a webhook node in n8n**
3. **Configure the webhook URL in your merchant dashboard**
4. **Send a test webhook from the merchant dashboard**
5. **Verify the webhook is received in n8n**

#### 7.5 Actions API Test
1. **Import the test workflow:** `ops/n8n/N8NProxy_Actions_Test_Suite.json`
2. **Configure the workflow variables:**
   - `proxyBase`: `https://app.n8ndesigner.com/api`
   - `merchantId`: Your test merchant ID
   - `bearer`: Your `ACTIONS_API_BEARER` token
3. **Execute the workflow**
4. **Verify all API calls succeed**

## Troubleshooting

### Common Issues and Solutions

#### 500 Internal Server Error
**Symptoms:** Application returns 500 error
**Solutions:**
1. Check file permissions: `chmod 755 storage/ bootstrap/cache/`
2. Verify .env configuration
3. Check Laravel logs: `storage/logs/laravel.log`
4. Ensure PHP 8.2+ is enabled

#### Database Connection Error
**Symptoms:** Database connection failed
**Solutions:**
1. Verify database credentials in .env
2. Check database exists and user has permissions
3. Test connection with: `php artisan tinker` then `DB::connection()->getPdo()`

#### Webhook Not Received
**Symptoms:** Webhooks not reaching n8n
**Solutions:**
1. Check merchant n8n URL configuration
2. Verify n8n instance is accessible
3. Check authentication settings
4. Review webhook logs in admin panel

#### Cron Job Not Running
**Symptoms:** Scheduled tasks not executing
**Solutions:**
1. Verify cron job syntax and paths
2. Check cron logs: `laravel-scheduler.log`
3. Test manually: `php artisan schedule:run`
4. Ensure PHP path is correct: `/usr/local/bin/ea-php82`

#### OAuth Token Issues
**Symptoms:** API actions failing with 401 errors
**Solutions:**
1. Check if merchant has valid OAuth tokens
2. Verify Salla app scopes are correct
3. Check token refresh mechanism
4. Review OAuth logs in admin panel

### Log Files and Monitoring

#### Application Logs
- **Laravel logs:** `storage/logs/laravel.log`
- **Cron logs:** `laravel-scheduler.log`
- **Webhook logs:** Admin panel → Webhooks
- **API logs:** Admin panel → Actions Audit

#### System Monitoring
1. **Check application health:** Visit `/health` endpoint
2. **Monitor webhook delivery rates**
3. **Review failed API calls**
4. **Check database performance**

### Security Considerations

#### Environment Security
- Keep `.env` file secure and never commit it
- Use strong passwords for all accounts
- Regularly rotate API tokens
- Monitor for suspicious activity

#### Database Security
- Use strong database passwords
- Limit database user permissions
- Regular database backups
- Monitor database access logs

#### Application Security
- Keep dependencies updated
- Monitor for security vulnerabilities
- Use HTTPS for all communications
- Implement rate limiting

## Maintenance

### Regular Tasks
1. **Monitor system health dashboard**
2. **Review failed webhook deliveries**
3. **Check OAuth token status**
4. **Update app settings as needed**
5. **Review and clean up old logs**

### Database Maintenance
1. **Archive old webhook events (older than 6 months)**
2. **Clean up expired OAuth tokens**
3. **Optimize audit log storage**
4. **Regular database backups**

### Performance Optimization
1. **Monitor response times**
2. **Optimize database queries**
3. **Cache frequently accessed data**
4. **Monitor memory usage**

## Support and Documentation

### Getting Help
1. **Check the admin dashboard for system status**
2. **Review webhook delivery logs**
3. **Monitor API action audits**
4. **Check Laravel logs for errors**

### Documentation
- **Merchant docs:** `/docs/merchant`
- **Admin docs:** `/docs/admin`
- **API documentation:** Available in admin panel

### Contact Information
- **Technical support:** [Your support email]
- **Documentation:** [Your docs URL]
- **Status page:** [Your status page URL]

## Copy into Salla Console

Use these exact settings in your Salla app configuration:

**Webhook URL:**
```
https://app.n8ndesigner.com/webhooks/salla
```

**App Events:**
```
https://app.n8ndesigner.com/app-events/authorized
```

**Webhook Security:**
- Strategy: Signature
- Secret: `519dd95fbd631b78020de2e36ae116c3`

**Required Scopes:**
- Orders: Read/Write
- Products: Read/Write
- Customers: Read/Write
- Marketing: Read/Write
- Categories: Read/Write
- Exports: Read/Write
- Webhooks: Read/Write
