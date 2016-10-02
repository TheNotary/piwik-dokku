#!/bin/bash
set -e

if [ ! -e piwik.php ]; then
  tar cf - --one-file-system -C /usr/src/piwik . | tar xf -
  chown -R www-data .
fi

service nginx start
php-fpm &


# set up the ENV variables
source automated_piwik_configs/12f_install.sh

# block until server is ready
until $(curl --output /dev/null --silent --head --fail http://127.0.0.1:3000/index.php); do
  printf '.'
  sleep 1
done
sleep 1

# This curls the piwik endpoints for creating the database and crap
php automated_piwik_configs/piwik_docker_install.php


exec "$@"

tail /var/log/dmesg -f
