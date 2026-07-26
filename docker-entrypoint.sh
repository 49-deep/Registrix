#!/bin/bash
set -e

# ── Rewrite Apache's Listen port to Railway's $PORT ─────────────
PORT="${PORT:-80}"

# Update the main Apache ports.conf
if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
fi

# Update any VirtualHost *:80 in sites-enabled
for conf in /etc/apache2/sites-enabled/*.conf; do
    if [ -f "$conf" ]; then
        sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" "$conf"
    fi
done

echo "Registrix: Apache will listen on port ${PORT}"

# ── Hand off to the CMD ─────────────────────────────────────────
exec "$@"
