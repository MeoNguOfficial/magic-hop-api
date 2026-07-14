FROM php:8.5-fpm

# Cài đặt các thư viện hệ thống và các PHP extension bắt buộc cho Laravel 13
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

RUN apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Lấy Composer phiên bản mới nhất vào container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc bên trong container
WORKDIR /var/www

# Copy toàn bộ code Laravel vào trong container
COPY . /var/www

# Chạy lệnh cài đặt các thư viện PHP (bỏ qua môi trường dev)
RUN composer install --no-dev --optimize-autoloader

# Cấp quyền ghi bắt buộc cho thư mục chứa log/cache của Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copy file cấu hình Nginx vào hệ thống
COPY nginx.conf /etc/nginx/sites-available/default

# Phân quyền chạy cho file script khởi động
RUN chmod +x /var/www/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/docker-entrypoint.sh"]