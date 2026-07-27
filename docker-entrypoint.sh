#!/bin/bash
set -e

# Prevent recursive invocation if startCommand repeats the entrypoint path
if [ "$1" = "docker-entrypoint.sh" ] || [ "$1" = "/usr/local/bin/docker-entrypoint.sh" ]; then
    shift
fi

# Ensure only mpm_prefork is active in Apache
if [ -d /etc/apache2/mods-enabled ]; then
    rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
    a2enmod mpm_prefork >/dev/null 2>&1 || true
fi

# ── Rewrite Apache's Listen port to Railway's $PORT ─────────────
PORT="${PORT:-8080}"

# Update the main Apache ports.conf
if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
fi

# Update any VirtualHost *:80 in sites-enabled
for conf in /etc/apache2/sites-enabled/*.conf; do
    if [ -f "$conf" ]; then
        sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/g" "$conf"
    fi
done

echo "Registrix: Apache will listen on port ${PORT}"

# ── Hand off to the CMD ─────────────────────────────────────────
exec "$@"
