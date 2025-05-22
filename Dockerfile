FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

WORKDIR /app
COPY . /app

CMD ["php", "-S", "0.0.0.0:8080", "index.php"]