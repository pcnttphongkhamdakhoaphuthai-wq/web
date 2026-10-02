# ==============================================================================
# Dockerfile - Hospital Web Support
# Base Image: PHP 8.2 with Apache on Debian Bookworm
# ==============================================================================

FROM php:8.2-apache-bookworm

# Metadata
LABEL maintainer="Hospital Support System"
LABEL description="Hospital Web Application running PHP 8.2 on Apache with Render PaaS support"

# 1. Cài đặt các thư viện hệ thống cần thiết (openssl, ca-certificates, curl, dev libs)
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libssl-dev \
    libzip-dev \
    openssl \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Cấu hình và cài đặt các PHP extensions: gd, mysqli, pdo_mysql, zip, opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        opcache

# 3. Kích hoạt các module Apache cần thiết (mod_rewrite, mod_headers)
RUN a2enmod rewrite headers

# 4. Cấu hình Apache cho phép .htaccess và giới hạn MPM Prefork (tiết kiệm RAM trên Render)
RUN { \
        echo '<Directory /var/www/html>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
        echo '<IfModule mpm_prefork_module>'; \
        echo '    StartServers             2'; \
        echo '    MinSpareServers          2'; \
        echo '    MaxSpareServers          4'; \
        echo '    MaxRequestWorkers        15'; \
        echo '    MaxConnectionsPerChild   1000'; \
        echo '</IfModule>'; \
    } > /etc/apache2/conf-available/hospital-app.conf \
    && a2enconf hospital-app

# 5. Cấu hình PHP production settings tối ưu
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'date.timezone = Asia/Ho_Chi_Minh'; \
        echo 'expose_php = Off'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.interned_strings_buffer = 8'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.revalidate_freq = 2'; \
    } > "$PHP_INI_DIR/conf.d/custom-hospital.ini"

# 6. Thiết lập thư mục làm việc DocumentRoot
WORKDIR /var/www/html

# 7. Sao chép toàn bộ mã nguồn vào container
COPY . /var/www/html/

# 8. Cài đặt entrypoint script, chuẩn hóa ký tự xuống dòng và cấp quyền thực thi
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

# 9. Khởi tạo các thư mục runtime cần thiết và phân quyền sở hữu sơ bộ
RUN mkdir -p /var/www/html/storage/backups \
             /var/www/html/storage/chat_logs \
             /var/www/html/storage/results \
             /var/www/html/storage/rate_limits \
             /var/www/html/storage/security \
             /var/www/html/storage/sessions \
             /var/www/html/storage/audit \
             /var/www/html/storage/password_resets \
             /var/www/html/storage/admin_notices \
             /var/www/html/uploads \
             /var/www/html/logs \
             /var/www/html/assets/doctor_photos \
             /var/www/html/assets/branding \
             /var/www/html/assets/news_media \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads /var/www/html/logs /var/www/html/assets \
    && chmod -R 775 /var/www/html/storage /var/www/html/uploads /var/www/html/logs

# 10. Khai báo cổng lắng nghe
EXPOSE 80 10000

# 11. Khởi chạy thông qua entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
