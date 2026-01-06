FROM ghcr.io/railwayapp/frankenphp:latest

WORKDIR /app

COPY . .

ENV PORT=8080

EXPOSE 8080
