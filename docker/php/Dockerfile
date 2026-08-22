FROM mlocati/php-extension-installer:2.11.12 AS php-extension-installer

FROM php:8.5.9-cli-bookworm

ENV COMPOSER_HTACCESS_PROTECT=0
ENV TZ=Europe/Moscow

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/install-php-extensions

RUN <<EOF bash
  set -euox pipefail
  shopt -s nullglob
  IFS=$'\n\t'

  apt-get update
  apt-get install --yes --no-install-recommends \
    ca-certificates locales openssl curl \
    git unzip zip tzdata

  echo ${TZ} > /etc/timezone
  echo "ru_RU.UTF-8 UTF-8" >> /etc/locale.gen
  echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen
  locale-gen ru_RU.UTF-8
  locale-gen en_US.UTF-8
  dpkg-reconfigure --frontend=noninteractive locales

  install-php-extensions @composer pgsql pdo_pgsql pdo_sqlite xdebug intl pcntl sockets

  apt-get clean autoclean
  apt-get autoremove --yes
  rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/* /tmp/* /var/tmp/*
EOF

ARG UID=1000
ENV UID=${UID}

ARG GID=1000
ENV GID=${GID}

ARG XDEBUG_MODE=coverage,debug
ENV XDEBUG_MODE=$XDEBUG_MODE

RUN <<EOF bash
  set -euox pipefail
  shopt -s nullglob
  IFS=$'\n\t'
  groupmod --gid=${GID} www-data
  usermod --uid=${UID} --gid=${GID} www-data
  mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

  {
      echo "file_uploads=On"
      echo "zend.exception_ignore_args=Off"
      echo "date.timezone=${TZ}"
      echo "memory_limit=512M"
      echo "upload_max_filesize=200M"
      echo "post_max_size=50M"
      echo "max_execution_time=300"
      echo "max_input_time=300"
      echo "expose_php = Off"
    } > "$PHP_INI_DIR/conf.d/zz-custom.ini"

  chown www-data:www-data /var/www
EOF

USER www-data

WORKDIR /var/www
