# SiteGround Deployment Runbook

This document explains how to package and deploy the N8NProxy Laravel application to SiteGround (using either direct file upload or SSH) and how to deploy the project to a self-managed Ubuntu server on Google Cloud Platform (GCP).

## 1. Package the application locally

> Run these steps on your local development machine where the repository is cloned.

1. **Open a terminal** and move into the Laravel application directory:
   ```bash
   cd app
   ```
2. **Execute the SiteGround build script** to produce a deployment bundle:
   ```bash
   bash scripts/build-siteground.sh
   ```
3. **Review the build output** in `app/build/`:
   - `siteground-n8nproxy.zip` — the production-ready archive.
   - `siteground-deployment-runbook.md` — generated guide with validation checks and troubleshooting tips.
4. **Verify the archive** by inspecting its contents before uploading:
   ```bash
   unzip -l build/siteground-n8nproxy.zip
   ```

Once the archive is ready, choose one of the SiteGround deployment options below.

---

## 2. SiteGround deployment (Option A: Direct file upload)

Use this path if you prefer SiteGround’s web interface or an SFTP client.

1. **Prepare SiteGround resources**
   - In SiteGround’s Site Tools, create a MySQL database and user (grant all privileges).
   - Note the database name, username, password, and host (usually `localhost`).

2. **Upload the deployment bundle**
   - Navigate to the desired target directory (e.g., `public_html/app/`).
   - Upload `siteground-n8nproxy.zip` through the File Manager or your SFTP client.

3. **Extract the archive**
   - Use the File Manager’s Extract function (or unzip via SFTP) so the project contents populate the target directory.

4. **Set file permissions**
   ```bash
   chmod 755 storage/
   chmod 755 bootstrap/cache/
   chmod 644 .env
   ```

5. **Create the environment file**
   ```bash
   cp env.example.siteground .env
   ```
   Open `.env` and update:
   - `APP_NAME`, `APP_URL`, `APP_ENV=production`.
   - Database credentials from the Site Tools database.
   - `ACTIONS_API_BASE_URL`, `ACTIONS_API_BEARER`, and any Salla-specific secrets.

6. **Finalize Laravel configuration**
   ```bash
   php artisan key:generate
   php artisan migrate --force
   ```

7. **Create an administrator account**
   ```bash
   php artisan tinker
   ```
   Inside Tinker run:
   ```php
   $user = new App\Models\User();
   $user->name = 'Admin';
   $user->email = 'admin@yourdomain.com';
   $user->password = bcrypt('your_admin_password');
   $user->is_admin = true;
   $user->save();
   exit;
   ```

8. **Schedule background jobs**
   - Create a cron job in Site Tools to run every minute:
     ```bash
     /usr/local/bin/php -d detect_unicode=0 /home/USERNAME/path/to/artisan schedule:run >> /home/USERNAME/laravel-scheduler.log 2>&1
     ```
   - Replace `USERNAME` with your SiteGround account name and adjust the artisan path.

9. **Configure Salla**
   - Update the Salla app settings with production URLs, webhook endpoints, and signature secret so events reach the deployed proxy.

10. **Validate the deployment**
    - Load the site in a browser.
    - Trigger a webhook with a sample payload and confirm it reaches n8n.
    - Reinstall the Salla app in a test store and run the bundled Actions workflow.

---

## 3. SiteGround deployment (Option B: SSH upload)

Use this method if you prefer command-line deployment over SSH.

1. **Collect SSH credentials**
   - Hostname: `ssh.n8ndesigner.com`
   - Port: `18765`
   - Username: `u2527-pka3nbglljwo`
   - Authentication: Use the SiteGround-provided SSH key and enter its passphrase when prompted.

2. **Upload the build via SCP**
   ```bash
   scp -P 18765 app/build/siteground-n8nproxy.zip \
       u2527-pka3nbglljwo@ssh.n8ndesigner.com:public_html/app/
   ```

3. **Log in through SSH**
   ```bash
   ssh -p 18765 u2527-pka3nbglljwo@ssh.n8ndesigner.com
   ```

4. **Unpack the archive**
   ```bash
   cd public_html/app
   unzip siteground-n8nproxy.zip
   ```

5. **Apply permissions**
   ```bash
   chmod 755 storage/ bootstrap/cache/
   chmod 644 .env
   ```

6. **Create and edit the environment file**
   ```bash
   cp env.example.siteground .env
   nano .env
   ```
   Update the same environment values described in Option A.

7. **Run Laravel post-deploy commands**
   ```bash
   php artisan key:generate
   php artisan migrate --force
   ```

8. **Seed an admin user** (optional if already created)
   ```bash
   php artisan tinker
   ```
   Execute the same PHP snippet as in Option A to create an administrator.

9. **Schedule cron and configure Salla**
   - Set up the Laravel scheduler cron job (Site Tools UI or `crontab -e`).
   - Confirm Salla webhooks and OAuth callbacks reference the new production URLs.

10. **Run validation checks**
    - Use `php artisan config:cache` to ensure configuration is cached.
    - Confirm application health via browser and webhook tests.

---

## 4. Deployment to a self-managed Ubuntu (GCP) server

Follow these instructions when deploying the project to a Google Cloud Platform Compute Engine instance running Ubuntu.

### 4.1. Prerequisites

- Ubuntu 22.04 LTS (or compatible) server with sudo access.
- Public DNS pointing to the VM’s IP (optional but recommended).
- Installed packages: `git`, `nginx`, `php8.2-fpm`, `php8.2-cli`, `php8.2-mysql`, `php8.2-xml`, `php8.2-mbstring`, `php8.2-curl`, `php8.2-zip`, `php8.2-bcmath`, `php8.2-gd`, `composer`, `mysql-server`.

### 4.2. Provision the server

1. **Update packages**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```
2. **Install dependencies**
   ```bash
   sudo apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql \
       php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd git unzip
   ```
3. **Install Composer globally** (if not already)
   ```bash
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php composer-setup.php --install-dir=/usr/local/bin --filename=composer
   rm composer-setup.php
   ```

### 4.3. Configure MySQL

1. Secure MySQL and set the root password:
   ```bash
   sudo mysql_secure_installation
   ```
2. Create the application database and user:
   ```sql
   CREATE DATABASE n8nproxy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'n8nproxy'@'localhost' IDENTIFIED BY 'strong_password_here';
   GRANT ALL PRIVILEGES ON n8nproxy.* TO 'n8nproxy'@'localhost';
   FLUSH PRIVILEGES;
   ```

### 4.4. Deploy the application

1. **Clone the repository** (or copy the SiteGround build zip if preferred):
   ```bash
   cd /var/www
   sudo git clone https://github.com/YourOrg/N8NProxy.git
   sudo chown -R $USER:$USER N8NProxy
   cd N8NProxy/app
   ```
2. **Install production dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build   # only if frontend assets need compiling
   ```
3. **Copy the environment file**
   ```bash
   cp .env.example .env
   ```
   Update `.env` with production values (app name/URL, database credentials, Salla secrets, n8n endpoints, etc.).
4. **Generate Laravel key and caches**
   ```bash
   php artisan key:generate
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. **Run migrations**
   ```bash
   php artisan migrate --force
   ```
6. **Create an admin user** (if needed) using the same Tinker snippet from the SiteGround steps.

### 4.5. Configure web server

1. **Set permissions**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```
2. **Create an Nginx server block** `/etc/nginx/sites-available/n8nproxy`:
   ```nginx
   server {
       listen 80;
       server_name example.com;
       root /var/www/N8NProxy/app/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.(php|phtml)$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```
3. **Enable the site and reload Nginx**
   ```bash
   sudo ln -s /etc/nginx/sites-available/n8nproxy /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```
4. **(Optional) Configure HTTPS** using Certbot:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d example.com -d www.example.com
   ```

### 4.6. Configure background workers

1. **Set up the Laravel scheduler**
   ```bash
   (crontab -l ; echo "* * * * * cd /var/www/N8NProxy/app && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1") | crontab -
   ```
2. **Queue worker (if used)**
   ```bash
   sudo nano /etc/systemd/system/laravel-worker.service
   ```
   ```
   [Unit]
   Description=Laravel Queue Worker

   [Service]
   User=www-data
   Group=www-data
   Restart=always
   ExecStart=/usr/bin/php /var/www/N8NProxy/app/artisan queue:work --sleep=3 --tries=3

   [Install]
   WantedBy=multi-user.target
   ```
   ```
   sudo systemctl daemon-reload
   sudo systemctl enable --now laravel-worker.service
   ```

### 4.7. Integrate with Salla and n8n

- Update Salla application settings with your GCP domain.
- Configure webhooks and OAuth callback URLs to the deployed Laravel endpoints.
- Confirm n8n receives events and the Actions API calls succeed using the production bearer token.

### 4.8. Post-deployment checks

- Visit the application URL to confirm it loads without errors.
- Review Laravel logs: `storage/logs/laravel.log`.
- Test webhook payloads against `/api` endpoints.
- Run any bundled n8n workflows to ensure end-to-end connectivity.

---

By following these runbooks, you can reliably deploy N8NProxy to SiteGround or a self-managed Ubuntu server on GCP. Keep the generated `siteground-deployment-runbook.md` nearby for environment-specific troubleshooting tips and validation procedures.
