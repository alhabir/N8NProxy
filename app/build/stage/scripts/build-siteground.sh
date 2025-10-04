#!/bin/bash

# N8NProxy SiteGround Build Script
# This script creates a production-ready package for SiteGround hosting

set -e

echo "🚀 Building N8NProxy for SiteGround deployment..."

# Create build directory
BUILD_DIR="build/siteground"
STAGE_DIR="build/stage"

echo "📁 Creating build directories..."
rm -rf $BUILD_DIR $STAGE_DIR
mkdir -p $BUILD_DIR $STAGE_DIR

echo "📋 Copying application files..."
# Copy all application files except vendor and node_modules
rsync -av --exclude='vendor/' --exclude='node_modules/' --exclude='.git/' --exclude='build/' --exclude='tests/' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' . $STAGE_DIR/

echo "📦 Installing production dependencies..."
cd $STAGE_DIR
composer install --no-dev --optimize-autoloader --no-interaction

echo "🔧 Optimizing for production..."
# Generate application key if not exists
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --ansi
fi

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "📝 Creating .htaccess files..."
# Root .htaccess
cat > .htaccess << 'EOF'
RewriteEngine On
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
EOF

# Public .htaccess (Laravel default)
cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

echo "📋 Creating deployment files..."
# Create .env.example.siteground
cat > .env.example.siteground << 'EOF'
APP_NAME=N8NProxy
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.n8ndesigner.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CHANGE_ME
DB_USERNAME=CHANGE_ME
DB_PASSWORD=CHANGE_ME

SALLA_CLIENT_ID=a5500786-2c22-4ca2-bab9-4cc6e0cd4906
SALLA_CLIENT_SECRET=f9b1a18cec45ac342bb9cf7bfd45ac73
SALLA_WEBHOOK_SECRET=519dd95fbd631b78020de2e36ae116c3
SALLA_API_BASE=https://api.salla.dev/admin/v2
SALLA_OAUTH_TOKEN_URL=https://accounts.salla.sa/oauth2/token

ACTIONS_API_BEARER=CHANGE_ME_STRONG

FORWARD_DEFAULT_TIMEOUT_MS=6000
FORWARD_SYNC_RETRIES=2
FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS=6
ALLOW_TEST_MODE=false
EOF

# Create README_RUNBOOK.md
cat > README_RUNBOOK.md << 'EOF'
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
EOF

echo "📦 Creating deployment package..."
cd ..
zip -r siteground-n8nproxy.zip siteground/ -x "*.DS_Store" "*/node_modules/*" "*/tests/*"

echo "✅ Build complete!"
echo "📁 Package created: build/siteground-n8nproxy.zip"
echo "📋 Runbook created: README_RUNBOOK.md"
echo ""
echo "Next steps:"
echo "1. Upload siteground-n8nproxy.zip to SiteGround"
echo "2. Extract and configure .env file"
echo "3. Run database migrations"
echo "4. Set up cron job"
echo "5. Configure Salla app settings"
echo ""
echo "See README_RUNBOOK.md for detailed instructions."
