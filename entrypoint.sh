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

# 2. Khởi tạo các thư mục runtime và bảo vệ lưu trữ bền vững (Persistent Disk)
echo "[ENTRYPOINT] Ensuring runtime and persistent storage directories exist..."
mkdir -p /var/www/html/storage/backups \
         /var/www/html/storage/chat_logs \
         /var/www/html/storage/results \
         /var/www/html/storage/rate_limits \
         /var/www/html/storage/security \
         /var/www/html/storage/sessions \
         /var/www/html/storage/audit \
         /var/www/html/storage/password_resets \
         /var/www/html/storage/admin_notices \
         /var/www/html/storage/uploads \
         /var/www/html/storage/doctor_photos \
         /var/www/html/storage/branding \
         /var/www/html/storage/news_media \
         /var/www/html/logs

# 3. Tạo symbolic links đảm bảo uploads và media được lưu trên Persistent Disk
if [ ! -L /var/www/html/uploads ]; then
    if [ -d /var/www/html/uploads ]; then
        cp -rn /var/www/html/uploads/* /var/www/html/storage/uploads/ 2>/dev/null || true
        rm -rf /var/www/html/uploads
    fi
    ln -s /var/www/html/storage/uploads /var/www/html/uploads
fi

mkdir -p /var/www/html/assets
for asset_dir in doctor_photos branding news_media; do
    if [ ! -L "/var/www/html/assets/${asset_dir}" ]; then
        if [ -d "/var/www/html/assets/${asset_dir}" ]; then
            cp -rn /var/www/html/assets/${asset_dir}/* /var/www/html/storage/${asset_dir}/ 2>/dev/null || true
            rm -rf "/var/www/html/assets/${asset_dir}"
        fi
        ln -s "/var/www/html/storage/${asset_dir}" "/var/www/html/assets/${asset_dir}"
    fi
done

# 4. Phân quyền thư mục cho user www-data (Apache)
echo "[ENTRYPOINT] Setting permissions for web server user (www-data)..."
chown -R www-data:www-data /var/www/html/storage \
                           /var/www/html/logs \
                           /var/www/html/assets

chmod -R 777 /var/www/html/storage \
             /var/www/html/logs

# 5. Khởi chạy Apache Foreground
echo "[ENTRYPOINT] Starting Apache web server..."
exec apache2-foreground
