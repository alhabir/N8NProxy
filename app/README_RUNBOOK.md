# Production Runbook (Ubuntu + Nginx)

This condensed runbook highlights the critical operational tasks for running N8NProxy on Ubuntu 25.04. For a full step-by-step guide see [`docs/README_UBUNTU_DEPLOY.md`](docs/README_UBUNTU_DEPLOY.md).

## Domains
- `app.n8ndesigner.com` → Admin panel, APIs, and webhooks
- `merchant.n8ndesigner.com` → Merchant portal
- Configure both A records to `34.28.46.125`.

## Server Bootstrap
1. Create sudo-enabled `deploy` user and secure the firewall with `ufw` (`OpenSSH` + `Nginx Full`).
2. Install packages: `php8.3`, `php8.3-fpm`, `php8.3-mysql`, `php8.3-xml`, `php8.3-mbstring`, `php8.3-intl`, `php8.3-gd`, `php8.3-curl`, `php8.3-zip`, `nginx`, `mariadb-server`, `certbot`, `python3-certbot-nginx`, `composer`, and `git`.
3. Create MySQL database/user pair `n8nproxy` / `n8nproxy` with full privileges.

## Directory Layout
```
/var/www/n8nproxy/
  releases/<timestamp>/
  shared/.env
  shared/storage/
  current -> releases/<timestamp>
```
Ensure `deploy` owns `/var/www/n8nproxy` and that `shared/storage` contains `framework/{cache,sessions,views}` and `logs/`.

## Environment
Copy `.env.production.example` into `/var/www/n8nproxy/shared/.env` and update:
- `APP_URL`, `ADMIN_APP_URL`, `MERCHANT_APP_URL`
- `SESSION_DOMAIN=.n8ndesigner.com`
- Database credentials
- `ACTIONS_API_BEARER`
- Existing Salla secrets remain unchanged

## Deployments
Execute from the server:
```bash
REPO_URL=git@github.com:n8ndesigner/N8NProxy.git \
GIT_REF=main \
APP_ROOT=/var/www/n8nproxy \
bash /path/to/repo/app/scripts/deploy.sh
```
The script installs dependencies, runs migrations, caches config/routes/views, links storage, and swaps the `current` symlink. Override `PHP_FPM_SERVICE` if the socket name differs.

## Nginx & SSL
- Use the server block templates in `ops/nginx/` (root: `/var/www/n8nproxy/current/public`).
- Enable with `ln -s ... sites-enabled` and reload Nginx.
- Request certificates:
  ```bash
  sudo certbot --nginx -d app.n8ndesigner.com -d merchant.n8ndesigner.com \
    --non-interactive --agree-tos -m info@n8ndesigner.com
  ```

## Scheduler
Add to the `deploy` user's crontab:
```
* * * * * cd /var/www/n8nproxy/current && /usr/bin/php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
```

## Initial Admin
```
cd /var/www/n8nproxy/current
php artisan app:create-first-admin
```
Creates/updates `info@n8ndesigner.com` with password `119115Ab30772` and `is_admin=1`.

## Post-Deploy Validation
1. Visit both subdomains over HTTPS.
2. `php artisan migrate:status`
3. Trigger a Salla webhook (order.created) and confirm forwarding to `https://n8nai.takaful-alarabia.com/webhook/salla`.
4. Import `ops/n8n/N8NProxy_Actions_Test_Suite.json` into n8n; run the workflow against `https://app.n8ndesigner.com/api` with the configured bearer token.

## Maintenance Checklist
- Monitor `systemctl status php8.3-fpm` and `nginx -t` after config changes.
- Rotate Nginx and Laravel scheduler logs via `logrotate`.
- Nightly `mysqldump` of `n8nproxy` to off-site storage.
- Keep OS packages updated (`apt-get upgrade`).

Following this runbook keeps the SiteGround-specific tooling deprecated and ensures the application runs natively on Ubuntu/Nginx.
