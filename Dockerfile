FROM php:5.6-fpm

RUN apt-get update && apt-get install -y \
      libjpeg-dev \
      libfreetype6-dev \
      libgeoip-dev \
      libpng12-dev \
      nginx \
      ssmtp \
      zip \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype-dir=/usr --with-png-dir=/usr --with-jpeg-dir=/usr \
 && docker-php-ext-install gd mbstring mysql pdo_mysql zip

RUN pecl install APCu geoip

ENV PIWIK_VERSION 2.16.2

RUN curl -fsSL -o piwik.tar.gz \
      "https://builds.piwik.org/piwik-${PIWIK_VERSION}.tar.gz" \
 && curl -fsSL -o piwik.tar.gz.asc \
      "https://builds.piwik.org/piwik-${PIWIK_VERSION}.tar.gz.asc" \
 && export GNUPGHOME="$(mktemp -d)" \
 && gpg --keyserver ha.pool.sks-keyservers.net --recv-keys 814E346FA01A20DBB04B6807B5DBD5925590A237 \
 && gpg --batch --verify piwik.tar.gz.asc piwik.tar.gz \
 && rm -r "$GNUPGHOME" piwik.tar.gz.asc \
 && tar -xzf piwik.tar.gz -C /usr/src/ \
 && rm piwik.tar.gz \
 && chfn -f 'Piwik Admin' www-data

COPY php.ini /usr/local/etc/php/php.ini

RUN curl -fsSL -o /usr/src/piwik/misc/GeoIPCity.dat.gz http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz \
 && gunzip /usr/src/piwik/misc/GeoIPCity.dat.gz

# Configure nginx
COPY piwik_nginx.conf /etc/nginx/sites-enabled/piwik.conf
RUN rm /etc/nginx/sites-enabled/default

# Configure App
RUN mkdir /var/www/html/automated_piwik_configs
ADD automated_piwik_configs/12f_install.sh /var/www/html/automated_piwik_configs
ADD automated_piwik_configs/piwik_docker_install.php /var/www/html/automated_piwik_configs
ADD automated_piwik_configs/populate_env_vars.sh /var/www/html/automated_piwik_configs
ADD automated_piwik_configs/config.ini.php /var/www/html/automated_piwik_configs


# WORKDIR is /var/www/html (inherited via "FROM php")
# "/entrypoint.sh" will populate it at container startup from /usr/src/piwik
VOLUME /var/www/html


COPY docker-entrypoint.sh /entrypoint.sh
RUN echo "php-fpm &" >> /root/.bash_history
RUN echo "service nginx start" >> /root/.bash_history
RUN echo "echo $DATABASE_URL" >> /root/.bash_history
ENTRYPOINT ["/entrypoint.sh"]
# CMD ["php-fpm"]
EXPOSE 3000
