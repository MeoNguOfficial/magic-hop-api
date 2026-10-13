#!/bin/sh

# 1. Tự động tạo file .env nếu chưa có (tránh lỗi do .env bị gitignore)
if [ ! -f /var/www/.env ]; then
    if [ -f /var/www/.env.example ]; then
        cp /var/www/.env.example /var/www/.env
    else
        touch /var/www/.env
    fi
fi

# 2. Đảm bảo APP_KEY luôn tồn tại (tránh crash 500 do thiếu key)
if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=base64:" /var/www/.env 2>/dev/null; then
    echo "Warning: APP_KEY is empty. Generating temporary application key..."
    php artisan key:generate --force
fi

# 3. Đồng bộ cổng Nginx theo biến môi trường PORT của Render (mặc định 80)
PORT="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/sites-available/default
sed -i "s/listen \[::\]:80;/listen [::]:${PORT};/g" /etc/nginx/sites-available/default

# 4. Tối ưu hóa cache cấu hình và route cho Laravel
php artisan optimize || php artisan config:clear

# 5. Tự động chạy migration nếu kết nối được DB (không làm sập container nếu DB offline)
php artisan migrate --force || echo "Warning: Migration failed or DB offline, continuing..."

# 6. Đảm bảo quyền ghi (Fix lỗi Permission denied trên Render)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 7. Khởi chạy PHP-FPM ở chế độ chạy ngầm
php-fpm -D

# 8. Khởi chạy Nginx ở chế độ foreground để giữ container luôn hoạt động
nginx -g "daemon off;"