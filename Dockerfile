FROM php:8.5-apache
# Copy toàn bộ code PHP vào thư mục chạy mặc định của Apache
COPY . /var/www/html/
#cross origin
RUN apt-get update && apt-get install -y libapache2-mod-headers
