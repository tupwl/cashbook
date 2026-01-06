FROM dunglas/frankenphp:php8.2

RUN docker-php-ext-install mysqli

WORKDIR /app
COPY . /app

EXPOSE 8080
