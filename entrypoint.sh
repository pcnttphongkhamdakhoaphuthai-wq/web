#!/bin/bash
set -e

# ==============================================================================
# Container Entrypoint Script for Render / Docker
# ==============================================================================

# 1. Cấu hình cổng Apache lắng nghe theo biến môi trường $PORT (Render tự gán, mặc định 80)
PORT="${PORT:-80}"
echo "[ENTRYPOINT] Configuring Apache to listen on port ${PORT}..."

if [ -f /etc/apache2/ports.conf ]; then
    echo "Listen ${PORT}" > /etc/apache2/ports.conf
fi

if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i -E "s/<VirtualHost \*:[0-9]+>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# 2. Khởi tạo và đảm bảo các thư mục runtime tồn tại
echo "[ENTRYPOINT] Ensuring runtime directories exist..."
mkdir -p /var/www/html/storage/backups \
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
         /var/www/html/assets/news_media

# 3. Phân quyền thư mục cho user www-data (Apache)
echo "[ENTRYPOINT] Setting permissions for web server user (www-data)..."
chown -R www-data:www-data /var/www/html/storage \
                           /var/www/html/uploads \
                           /var/www/html/logs \
                           /var/www/html/assets

chmod -R 777 /var/www/html/storage \
             /var/www/html/uploads \
             /var/www/html/logs

# 4. Khởi chạy Apache Foreground
echo "[ENTRYPOINT] Starting Apache web server..."
exec apache2-foreground
