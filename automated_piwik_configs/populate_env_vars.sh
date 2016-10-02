#!/bin/bash

generate-salt()
{
  cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w ${1:-32} | head -n 1
}

# parse DATABASE_URL into its segments for piwik
# extract the protocol
proto="`echo $DATABASE_URL | grep '://' | sed -e's,^\(.*://\).*,\1,g'`"
# remove the protocol
url=`echo $DATABASE_URL | sed -e s,$proto,,g`

# extract the user and password (if any)
userpass="`echo $url | grep @ | cut -d@ -f1`"
pass=`echo $userpass | grep : | cut -d: -f2`
if [ -n "$pass" ]; then
    user=`echo $userpass | grep : | cut -d: -f1`
else
    user=$userpass
fi

# extract the host -- updated
hostport=`echo $url | sed -e s,$userpass@,,g | cut -d/ -f1`
port=`echo $hostport | grep : | cut -d: -f2`
if [ -n "$port" ]; then
    host=`echo $hostport | grep : | cut -d: -f1`
else
    host=$hostport
fi

# extract the path (if any)
path="`echo $url | grep / | cut -d/ -f2-`"

export ADMIN_LOGIN=admin
export ADMIN_EMAIL=admin@example.org
export ADMIN_PASSWORD=password1

export DB_HOST=$host
export DB_USERNAME=$user
export DB_PASSWORD=$pass
export DB_NAME=$path
export DB_PORT=$port
export DB_PREFIX=piwik_

# don't run on heroku... it will hang for some reason...
if [ -z "${DYNO}" ]; then
  export SECRET_TOKEN=`generate-salt`
fi

sed -i s/#DB_HOST/$DB_HOST/ vendor/piwik/piwik/config/config.ini.php
sed -i s/#DB_USERNAME/$DB_USERNAME/ vendor/piwik/piwik/config/config.ini.php
sed -i s/#DB_PASSWORD/$DB_PASSWORD/ vendor/piwik/piwik/config/config.ini.php
sed -i s/#DB_NAME/$DB_NAME/ vendor/piwik/piwik/config/config.ini.php
sed -i s/#DB_PORT/$DB_PORT/ vendor/piwik/piwik/config/config.ini.php

sed -i s/#DB_PREFIX/$DB_PREFIX/ vendor/piwik/piwik/config/config.ini.php
sed -i s/#SECRET_TOKEN/$SECRET_TOKEN/ vendor/piwik/piwik/config/config.ini.php
