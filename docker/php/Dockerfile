# 使用官方的 PHP-FPM 镜像，这里以 8.2-fpm-alpine 为例
FROM php:8.2-fpm-alpine

# 安装常用的工具和 PHP 扩展
RUN apk update && apk add --no-cache \
    $PHPIZE_DEPS \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql opcache zip \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# 设置工作目录
WORKDIR /var/www/html
