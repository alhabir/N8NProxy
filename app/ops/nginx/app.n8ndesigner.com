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
