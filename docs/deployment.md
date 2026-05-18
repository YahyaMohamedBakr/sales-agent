# Deployment Guide

## Requirements

- PHP 8.4+
- PostgreSQL 16+ (with pgvector)
- Redis 7+
- Composer 2
- Node.js 22+
- Supervisor (for queue workers)

## Production .env

```bash
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sales_agent
DB_USERNAME=sales_agent
DB_PASSWORD=strong_password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG....

FILESYSTEM_DISK=local

META_PAGE_ID=your_page_id
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_PAGE_ACCESS_TOKEN=EAAT...
META_WEBHOOK_VERIFY_TOKEN=choose_a_random_token

OPENAI_API_KEY=sk-...
```

## Deployment Steps

```bash
# 1. Clone & install
git clone ... /var/www/sales_agent
cd /var/www/sales_agent
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate --force

# 4. Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Queue workers (via Supervisor)
sudo cp horizon.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start horizon:*

# 6. Scheduler (crontab)
echo "* * * * * cd /var/www/sales_agent && php artisan schedule:run >> /dev/null 2>&1" | crontab -

# 7. Web server (nginx)
# Point document root to /var/www/sales_agent/public
```

## Supervisor Config

```ini
; /etc/supervisor/conf.d/horizon.conf
[program:horizon]
process_name=%(program_name)s
command=php /var/www/sales_agent/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sales_agent/storage/logs/horizon.log
stopwaitsecs=360
```

## Nginx Config

```nginx
server {
    listen 80;
    server_name sales-agent.example.com;
    root /var/www/sales_agent/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Webhook Setup (Meta)

1. Go to [Meta Developer Console](https://developers.facebook.com/apps)
2. App → Webhooks → Page
3. Subscribe to: `feed`, `messages`, `messaging_postbacks`
4. Callback URL: `https://sales-agent.example.com/webhook/meta`
5. Verify Token: (same as `META_WEBHOOK_VERIFY_TOKEN`)
6. Add Page to webhook

## HTTPS

Webhooks **require HTTPS**:

- **Cloudflare** — free SSL, set Full (Strict)
- **LetsEncrypt** — `certbot --nginx -d sales-agent.example.com`
- **Ngrok** — for local development: `ngrok http 8000`
