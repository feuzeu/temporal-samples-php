FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
  nano \
  bash \
  libzip-dev \
  unzip \
  libonig-dev

# Install PHP Extensions
ENV CFLAGS="$CFLAGS -D_GNU_SOURCE"
RUN docker-php-ext-install zip \
  && docker-php-ext-install opcache sockets mbstring \
  && docker-php-ext-enable opcache sockets mbstring

# Protobuf and GRPC
ENV PROTOBUF_VERSION "3.25.1"
RUN pecl channel-update pecl.php.net
RUN pecl install protobuf-${PROTOBUF_VERSION} grpc \
    && docker-php-ext-enable protobuf grpc

# Install Temporal CLI
COPY --from=temporalio/admin-tools /usr/local/bin/tctl /usr/local/bin/tctl

# Install Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Wait for Temporal service to star up
COPY wait-for-temporal.sh /usr/local/bin
RUN chmod +x /usr/local/bin/wait-for-temporal.sh

# Copy application codebase
WORKDIR /var/app
COPY app/ /var/app

RUN composer install

# Load binaries
RUN vendor/bin/dload get --no-interaction
