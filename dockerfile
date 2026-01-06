FROM php:8.2-cli

# ติดตั้ง extension ที่ PHP + MySQL ต้องใช้
RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app
COPY . .

# Railway จะ inject PORT มาให้
CMD ["sh", "start.sh"]
