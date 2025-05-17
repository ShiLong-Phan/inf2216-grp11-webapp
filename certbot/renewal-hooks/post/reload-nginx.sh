#!/bin/sh
# This hook is run after the renewal attempt succeeds

# Exit hook with success even if nginx reload fails
docker-compose exec -T web nginx -s reload || true
echo "$(date): Certificate renewal hook executed" >> /var/log/letsencrypt/renewal-hook.log
