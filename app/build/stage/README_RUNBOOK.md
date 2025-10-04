# N8NProxy SiteGround Deployment Runbook

## Prerequisites
- SiteGround hosting account
- PHP 8.2+ enabled
- MySQL database
- SSL certificate (for production)

## Deployment Steps

### 1. Database Setup
1. Log into SiteGround cPanel
2. Go to MySQL Databases
3. Create a new database (e.g., `n8nproxy_prod`)
4. Create a database user with full privileges
5. Note down the database credentials

### 2. File Upload
1. Extract the `siteground-n8nproxy.zip` file
2. Upload all files to your subdomain directory (e.g., `app.n8ndesigner.com`)
3. Ensure all files are uploaded to the correct directory

### 3. Environment Configuration
1. Copy `.env.example.siteground` to `.env`
2. Update the following values:
   - `APP_KEY`: Generate with `php artisan key:generate`
   - `DB_DATABASE`: Your database name
   - `DB_USERNAME`: Your database username
   - `DB_PASSWORD`: Your database password
   - `ACTIONS_API_BEARER`: Generate a strong random token
   - `APP_URL`: Your domain URL

### 4. Database Migration
1. Run: `php artisan migrate --force`
2. Create admin user: `php artisan tinker`
   ```php
   $user = new App\Models\User();
   $user->name = 'Admin';
   $user->email = 'admin@example.com';
   $user->password = bcrypt('your_password');
   $user->is_admin = true;
   $user->save();
   ```

### 5. Cron Setup
1. Go to SiteGround cPanel → Cron Jobs
2. Add this cron job:
   ```
   * * * * * /usr/local/bin/ea-php82 /home/USER/public_html/artisan schedule:run >> /home/USER/laravel-scheduler.log 2>&1
   ```
   Replace `USER` with your SiteGround username

### 6. Salla Console Configuration
Configure your Salla app with these exact settings:

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

### 7. Testing
1. Visit your domain to verify the app loads
2. Test webhook endpoint with a sample payload
3. Install the app in a test Salla store
4. Verify webhook delivery in n8n

## Troubleshooting

### Common Issues
- **500 Error**: Check file permissions and .env configuration
- **Database Connection**: Verify database credentials
- **Webhook Failures**: Check n8n URL configuration
- **Cron Not Running**: Verify cron job syntax and paths

### Logs
- Application logs: `storage/logs/laravel.log`
- Cron logs: `laravel-scheduler.log`
- Webhook logs: Check admin panel

### Support
- Check the admin dashboard for system status
- Review webhook delivery logs
- Monitor API action audits
- Contact support with specific error messages

## Security Notes
- Keep your `.env` file secure and never commit it
- Use strong passwords for all accounts
- Regularly update dependencies
- Monitor for suspicious activity
- Backup your database regularly
