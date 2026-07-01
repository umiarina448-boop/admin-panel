FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app
COPY . /app

ENV SERVER_NAME=":8080"

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
