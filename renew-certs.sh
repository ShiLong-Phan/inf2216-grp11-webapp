#!/bin/bash

# Renew certificates
echo "$(date): Running certificate renewal" >> ./certbot-renewal.log
docker-compose run --rm certbot renew --quiet

# Check renewal status and reload nginx if successful
if [ $? -eq 0 ]; then
  echo "$(date): Certificate renewal succeeded, reloading Nginx" >> ./certbot-renewal.log
  docker-compose exec -T web nginx -s reload
else
  echo "$(date): Certificate renewal failed or wasn't needed" >> ./certbot-renewal.log
fi
