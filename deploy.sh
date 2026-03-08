#!/usr/bin/env bash
# Deploy to production: pull latest in xampp-php-project, then sync to /var/www/html
# Run from your Mac (e.g. in Cursor): ./deploy.sh   or   bash deploy.sh

set -e
SERVER="root@64.23.181.64"

ssh "$SERVER" 'cd /var/www/xampp-php-project && rm -f deploy.sh && git pull && composer install --no-dev && rsync -av --exclude=.git --exclude="*.swp" /var/www/xampp-php-project/ /var/www/html/ && mkdir -p /var/www/calcforadvisors && rsync -av --exclude=.git --exclude="*.swp" /var/www/xampp-php-project/calcforadvisors/ /var/www/calcforadvisors/'
echo "Deploy done. Check https://ronbelisle.com and https://calcforadvisors.com"
