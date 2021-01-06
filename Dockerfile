FROM php:7.4-apache

ENV DEBUG="0" \
    PHP_VERSION="7.4" \
    APP_DIR="/app" \
    PUBLIC_DIR="public" \
    TZ="America/Sao_Paulo" \
    UID="1000"

ENV APACHE_DOCUMENT_ROOT="${APP_DIR}/${PUBLIC_DIR}" \
    APACHE_LOG_DIR="/var/log/apache2"

RUN usermod -u ${UID} www-data
RUN groupmod -g ${UID} www-data

RUN  set -eux \
    ; \
    sed -ri -e \
        's!/var/www/html!\$\{APACHE_DOCUMENT_ROOT\}!g' \
        ${APACHE_CONFDIR}/sites-available/*.conf \
    ; \
    sed -ri -e \
        's!/var/www/!\$\{APACHE_DOCUMENT_ROOT\}!g' \
        ${APACHE_CONFDIR}/apache2.conf \
        ${APACHE_CONFDIR}/conf-available/*.conf \
    ; \
    sed -i -e \
        's/access.log combined/access.log combined env=!APACHE_LOG_DISABLED/g' \
        ${APACHE_CONFDIR}/sites-enabled/*.conf \
    ; \
    a2enmod rewrite

RUN apt-get update && \
    apt-get install --yes --no-install-recommends \
        tzdata \
        git \
        zip \
        unzip \
    && \
    apt-get clean; \
    rm -rf \
        /var/lib/apt/lists/* \
        /tmp/* \
        /var/tmp/* \
    ;

RUN echo "${TZ}" > /etc/timezone \
    ln -vsfT /usr/share/zoneinfo/${TZ} /etc/localtime

RUN docker-php-ext-configure \
        zip --with-libzip \
    ; \
    docker-php-ext-install \
        sockets \
        pdo_mysql \
        mbstring \
        bcmath \
        json \
        opcache \
        zip \
    ; \
    pecl install xdebug

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR ${APP_DIR}

COPY composer.json ${APP_DIR}/

RUN composer install \
        --prefer-dist \
        --no-dev \
        --no-scripts \
        --no-progress \
        --no-suggest \
        --optimize-autoloader \
        --classmap-authoritative \
        --no-ansi \
        --no-interaction \
        --no-plugins \
    ;

COPY . ${APP_DIR}
RUN ln -vsfT ${APP_DIR}/bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
RUN chown -R www-data:www-data ${APP_DIR}/vendor