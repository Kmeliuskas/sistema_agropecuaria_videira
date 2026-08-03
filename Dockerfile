# Multi-stage build para Laravel 13 (PHP 8.4)
FROM php:8.4-fpm-alpine AS base

# Extensões necessárias
RUN apk add --no-cache \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    giflib-dev \
    webp-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        bcmath \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        zip \
        opcache

# Redis (phpredis) via pecl
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ---------------------------------------------------------------------------
# Estágio "node": compila o front (Tailwind/Vite) e gera public/build.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS node
WORKDIR /var/www
COPY package.json ./
# `npm install` (e não `npm ci`) porque o lock de optionalDependencies
# nativas (@emnapi/core/runtime) diverge entre plataformas/versões de npm
# (Windows x container), o que fazia `npm ci` falhar com EUSAGE. O
# `install` recalcula o grafo e baixa a versão correta no container.
# ATENÇÃO: NÃO usar --omit=dev — o vite/tailwind (necessários ao build)
# estão em devDependencies.
RUN npm install --no-audit --no-fund
COPY . .
RUN npm run build

# ---------------------------------------------------------------------------
# Estágio final: junta vendor (PHP) + build (node) na imagem autocontida.
# ---------------------------------------------------------------------------
FROM base AS app

# Instala dependências (vendor). --no-scripts evita o `php artisan
# package:discover` do post-autoload-dump, que falharia porque o código
# completo (bootstrap/app.php) ainda não foi copiado. O package:discover
# roda depois, no `php artisan optimize:clear` (linha abaixo).
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

COPY . .

# Traz o assets compilados pelo estágio node.
COPY --from=node /var/www/public/build ./public/build

# Entrypoint: roda migrate + optimize a cada deploy antes do php-fpm.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www

EXPOSE 9000

# O entrypoint roda as migrations e sobe o php-fpm (CMD) em seguida.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Estágio "nginx": web server que serve os assets compilados (public/build)
# e repassa PHP para o app. Usa a imagem final (que já tem o build do node)
# para que o CSS/JS esteja disponível sem bind do host.
# ---------------------------------------------------------------------------
FROM nginx:alpine AS nginx

# Copia o código público (com public/build já compilado) da imagem final.
COPY --from=app /var/www/public /var/www/public

# O conf padrão é injetado via volume no compose
# (./docker/nginx/default.conf -> /etc/nginx/conf.d/default.conf), mas
# garantimos o root correto caso o volume não seja montado.
RUN sed -i 's#/usr/share/nginx/html#/var/www/public#g' /etc/nginx/conf.d/default.conf 2>/dev/null || true

EXPOSE 80

