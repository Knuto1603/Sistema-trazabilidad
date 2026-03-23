#!/bin/sh
set -e

# Decodifica las JWT keys desde env vars base64 a archivos PEM
if [ -n "$JWT_PRIVATE_KEY_BASE64" ]; then
    echo "$JWT_PRIVATE_KEY_BASE64" | base64 -d > /var/www/html/config/jwt/private.pem
    chmod 644 /var/www/html/config/jwt/private.pem
fi

if [ -n "$JWT_PUBLIC_KEY_BASE64" ]; then
    echo "$JWT_PUBLIC_KEY_BASE64" | base64 -d > /var/www/html/config/jwt/public.pem
    chmod 644 /var/www/html/config/jwt/public.pem
fi

# Calentar cache de produccion para cada app
php apps/security/bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
php apps/core/bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Asegurar que www-data tenga acceso a todo lo generado por root durante warmup
chown -R www-data:www-data apps/security/var apps/core/var

exec "$@"
