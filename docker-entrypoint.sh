#!/bin/bash
set -e

# Disable conflicting Apache MPM modules and ensure mpm_prefork is active
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# ── Rewrite Apache's Listen port to Railway's $PORT ─────────────
PORT="${PORT:-8080}"

if [ -f /etc/apache2/ports.conf ]; then
    sed -i -E "s/Listen .*/Listen ${PORT}/g" /etc/apache2/ports.conf
fi

if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i -E "s/<VirtualHost \*:.*/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi

for conf in /etc/apache2/sites-enabled/*.conf; do
    if [ -f "$conf" ]; then
        sed -i -E "s/<VirtualHost \*:.*/<VirtualHost *:${PORT}>/g" "$conf"
    fi
done

echo "Registrix: Apache starting on port ${PORT}"

# ── Hand off to apache2-foreground ─────────────────────────────────────────
if [ "$1" = "apache2-foreground" ]; then
    exec "$@"
else
    exec apache2-foreground "$@"
fi
