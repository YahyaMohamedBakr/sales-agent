#!/usr/bin/env bash
set -euo pipefail

# ──────────────────────────────────────────────────
# AI Sales Agent — التثبيت التلقائي الكامل
# ──────────────────────────────────────────────────
# شغّل الأمر ده على السيرفر:
#   curl -sL https://github.com/YahyaMohamedBakr/sales-agent/raw/main/install.sh | bash
# ──────────────────────────────────────────────────

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${CYAN}[INFO]${NC}  $1"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $1"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $1"; exit 1; }

# ── 1. المتطلبات ──
info "التحقق من المتطلبات..."

command -v git    >/dev/null || fail "git غير مثبت"
command -v php    >/dev/null || fail "PHP غير مثبت"
command -v composer >/dev/null || fail "Composer غير مثبت"
command -v node   >/dev/null || fail "Node.js غير مثبت"
command -v npm    >/dev/null || fail "npm غير مثبت"
command -v mysql  >/dev/null || echo "mysql client غير موجود (حاول install)"
command -v redis-cli >/dev/null || warn "Redis client غير موجود (لو تستخدم queue)"

ok "كل المتطلبات موجودة"

# ── 3. استنساخ المشروع ──
APP_DIR="${APP_DIR:-/var/www/sales-agent}"

if [ -d "$APP_DIR" ]; then
    warn "المجلد $APP_DIR موجود. يتم التحديث..."
    cd "$APP_DIR" && git pull origin main
else
    info "استنساخ المشروع..."
    git clone https://github.com/YahyaMohamedBakr/sales-agent.git "$APP_DIR"
    cd "$APP_DIR"
fi

ok "المشروع جاهز في $APP_DIR"

# ── 4. ملف البيئة ──
if [ ! -f .env ]; then
    info "إنشاء ملف .env..."
    cp .env.example .env
    php artisan key:generate --force
    info "⚠  مهم: افتح ملف .env وعدل الداتابيز والـ API keys"
    echo -e "${YELLOW}   nano $APP_DIR/.env${NC}"
else
    ok ".env موجود مسبقاً"
fi

# ── 5. تثبيت باكجات PHP ──
info "تثبيت باكجات PHP..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
ok "PHP dependencies installed"

# ── 6. تثبيت باكجات JS + بناء الواجهة ──
info "تثبيت باكجات JS..."
npm ci --no-optional
ok "JS dependencies installed"

info "بناء الواجهة..."
npm run build
ok "Frontend built"

# ── 7. إنشاء الداتابيز ──
if command -v mysql &>/dev/null; then
    DB_NAME=$(grep DB_DATABASE .env | cut -d= -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d= -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d= -f2)

    if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
        info "إنشاء قاعدة البيانات $DB_NAME..."
        mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || warn "ماقدرش أنشئ الداتابيز — تأكد من الصلاحيات"
        ok "Database ready"
    fi
fi

# ── 8. المايجريشن + السيد ──
info "تشغيل المايجريشن..."
php artisan migrate --force
ok "Migrations done"

info "إضافة المستخدم الافتراضي..."
php artisan db:seed --force
ok "Admin user created: admin@example.com / password"

# ── 9. تحسينات الإنتاج ──
info "تحسينات الأداء..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
ok "Cache warmed"

# ── 10. الصلاحيات ──
info "ضبط الصلاحيات..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
ok "Permissions set"

# ── 11. الرابط المباشر ──
php artisan storage:link --force 2>/dev/null || true

# ── 12. إعداد Horizon (اختياري) ──
if command -v supervisorctl &>/dev/null; then
    info "إنشاء ملف Supervisor لـ Horizon..."
    sudo bash -c "cat > /etc/supervisor/conf.d/horizon.conf" <<'SUPER'
[program:horizon]
process_name=%(program_name)s
command=php /var/www/sales-agent/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sales-agent/storage/logs/horizon.log
stopwaitsecs=3600
SUPER
    sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start horizon 2>/dev/null || warn "Supervisor: تأكد من المسار"
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     ✅  التثبيت اكتمل بنجاح              ║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║  الموقع:     http://YOUR_SERVER_IP        ║${NC}"
echo -e "${GREEN}║  دخول:       admin@example.com / password ║${NC}"
echo -e "${GREEN}║  الإعدادات:  /settings                     ║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║  قبل التشغيل:                              ║${NC}"
echo -e "${GREEN}║  1. عدل .env: nano .env                     ║${NC}"
echo -e "${GREEN}║  2. حط API keys من /settings                ║${NC}"
echo -e "${GREEN}║  3. php artisan serve --host=0.0.0.0       ║${NC}"
echo -e "${GREEN}║     أو استخدم Nginx + PHP-FPM              ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
