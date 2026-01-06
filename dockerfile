FROM dunglas/frankenphp:php8.2

# ติดตั้ง mysqli + pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ตั้ง working directory
WORKDIR /app

# copy ไฟล์ทั้งหมดเข้า container
COPY . /app

# เปิด port (Railway ใช้ 8080)
EXPOSE 8080

# start frankenphp
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# force rebuild
