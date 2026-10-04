#!/bin/sh

# Tối ưu hóa cache cấu hình và route cho Laravel 13 trên môi trường production
php artisan optimize

# Tự động chạy database migration khi deploy bản mới (Rất tiện, không cần vào Shell chạy tay)
php artisan migrate --force

# Đảm bảo quyền ghi (Fix lỗi Permission denied trên Render)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Khởi chạy PHP-FPM ở chế độ chạy ngầm
php-fpm -D

# Khởi chạy Nginx ở chế độ foreground để giữ container luôn hoạt động
nginx -g "daemon off;"