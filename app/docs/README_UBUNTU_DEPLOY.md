# N8NProxy Ubuntu/Nginx Deployment Guide

This guide covers end-to-end deployment of the N8NProxy Laravel 11 application on a self-managed Ubuntu 25.04 server with Nginx, PHP-FPM, and MariaDB/MySQL. The target architecture serves two subdomains from a single codebase:

- **Admin panel + API + webhooks:** `app.n8ndesigner.com`
- **Merchant panel:** `merchant.n8ndesigner.com`

## 1. DNS

Create the following DNS A records pointing to `34.28.46.125`:

| Host | Record | Value |
| --- | --- | --- |
| `app.n8ndesigner.com` | A | `34.28.46.125` |
| `merchant.n8ndesigner.com` | A | `34.28.46.125` |

Propagation can take a few minutes; verify with `dig app.n8ndesigner.com +short`.

## 2. System Bootstrap (run as root once)

```bash
apt-get update
apt-get install -y software-properties-common curl git ufw
adduser --disabled-password --gecos "" deploy
usermod -aG sudo deploy
mkdir -p /var/www/n8nproxy
chown deploy:deploy /var/www/n8nproxy
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
```

## 3. Install PHP, Composer, Nginx, MariaDB (as root)

```bash
apt-get install -y php8.3 php8.3-fpm php8.3-cli \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl \
    php8.3-mysql php8.3-bcmath php8.3-gd php8.3-readline
apt-get install -y nginx mariadb-server certbot python3-certbot-nginx
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

### Database

```bash
mysql -u root
```

Inside the MariaDB shell:

```sql
CREATE DATABASE n8nproxy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'n8nproxy'@'localhost' IDENTIFIED BY 'change_me';
GRANT ALL PRIVILEGES ON n8nproxy.* TO 'n8nproxy'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Nginx Virtual Hosts

Create `/etc/nginx/sites-available/app.n8ndesigner.com`:

```nginx
server {
    listen 80;
    server_name app.n8ndesigner.com;
    root /var/www/n8nproxy/current/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~* \.(?:ico|css|js|gif|jpe?g|png|svg|webp)$ {
        expires 7d;
        access_log off;
    }
}
```

Create `/etc/nginx/sites-available/merchant.n8ndesigner.com` (identical server block except for `server_name`). Enable both:

```bash
ln -s /etc/nginx/sites-available/app.n8ndesigner.com /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/merchant.n8ndesigner.com /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

## 5. Deploy User Environment (as `deploy`)

```bash
sudo -iu deploy
cd /var/www/n8nproxy
mkdir -p shared/storage/framework/{cache,sessions,views} shared/storage/logs
cp /path/to/repo/app/.env.production.example shared/.env
nano shared/.env  # update DB password, ACTIONS_API_BEARER, etc.
```

### First Deployment

```bash
cd /var/www/n8nproxy
REPO_URL=git@github.com:n8ndesigner/N8NProxy.git GIT_REF=main \
  bash /path/to/repo/app/scripts/deploy.sh
```

The script will:

1. Clone the requested git ref into `releases/<timestamp>`
2. Flatten the repository so `current/public` points to Laravel's `public`
3. Install dependencies with Composer
4. Link `shared/.env` and `shared/storage`
5. Generate `APP_KEY` if missing
6. Run `php artisan migrate --force`
7. Cache config/routes/views, create the storage symlink
8. Switch `current` symlink to the new release and reload `php8.3-fpm`

Subsequent deployments simply rerun the same script with an updated `GIT_REF` or tag.

## 6. SSL Certificates

Once DNS resolves to the server and Nginx is serving HTTP:

```bash
sudo certbot --nginx \
  -d app.n8ndesigner.com \
  -d merchant.n8ndesigner.com \
  --non-interactive --agree-tos -m info@n8ndesigner.com
```

Verify the systemd timer:

```bash
systemctl status certbot.timer
```

Certificates renew automatically; Nginx is reloaded by Certbot hooks.

## 7. Scheduler Cron

As the `deploy` user:

```bash
crontab -e
```

Add:

```
* * * * * cd /var/www/n8nproxy/current && /usr/bin/php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
```

Ensure `/var/log/laravel-scheduler.log` is writable by `deploy` (or adjust path).

## 8. Seed the First Admin

After the first deployment completes:

```bash
cd /var/www/n8nproxy/current
php artisan app:create-first-admin
```

This command provisions `info@n8ndesigner.com` with password `119115Ab30772` and `is_admin=1`. Override with `--email=` or `--password=` if required.

## 9. Panel URLs & Configuration

- Admin login/dashboard/API/webhooks: `https://app.n8ndesigner.com`
- Merchant onboarding & docs: `https://merchant.n8ndesigner.com`
- Update any marketing links to reference the correct subdomain via `config/panels.php`.

Set the following environment variables in `shared/.env`:

```
APP_URL=https://app.n8ndesigner.com
ADMIN_APP_URL=https://app.n8ndesigner.com
MERCHANT_APP_URL=https://merchant.n8ndesigner.com
SESSION_DOMAIN=.n8ndesigner.com
ACTIONS_API_BEARER=<strong-random-token>
```

## 10. Salla Console Setup

- **Webhook URL:** `https://app.n8ndesigner.com/webhooks/salla`
- **App Authorized URL:** `https://app.n8ndesigner.com/app-events/authorized`
- **Webhook Secret:** `519dd95fbd631b78020de2e36ae116c3`
- Ensure required scopes (Orders/Products/Customers/Marketing/Categories/Exports/Webhooks) are enabled.

## 11. Smoke Tests

1. Visit `https://app.n8ndesigner.com` and confirm the admin panel loads (login requires the seeded admin).
2. Visit `https://merchant.n8ndesigner.com` to ensure the merchant portal and registration flow render.
3. Run a database connectivity check:
   ```bash
   php artisan migrate:status
   ```
4. Execute the n8n Actions test suite after importing `ops/n8n/N8NProxy_Actions_Test_Suite.json`—the workflow defaults to `https://app.n8ndesigner.com/api`.
5. Trigger a webhook from a Salla sandbox store; confirm the event appears in the merchant dashboard and forwards to `https://n8nai.takaful-alarabia.com/webhook/salla`.

## 12. Backups & Maintenance

- Database: enable daily `mysqldump` to off-site storage.
- Application: retain several `releases/` directories for rollbacks; delete old ones periodically.
- Logs: rotate `/var/log/nginx/*.log` and `/var/log/laravel-scheduler.log` with `logrotate`.
- Security: keep `apt-get upgrade` current, monitor `systemctl status php8.3-fpm` and `nginx -t` after changes.

With the above steps complete, the application is production-ready on Ubuntu 25.04 with native Nginx hosting.
