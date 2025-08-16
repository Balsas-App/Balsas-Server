FROM php:8.2-cli

# Instala curl, unzip e wget (necessários)
RUN apt-get update && apt-get install -y \
    curl unzip wget gnupg \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    zlib1g-dev

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Instala ngrok (sem usar sudo)
RUN curl -sSL https://ngrok-agent.s3.amazonaws.com/ngrok.asc \
    | tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null \
    && echo "deb https://ngrok-agent.s3.amazonaws.com buster main" \
    | tee /etc/apt/sources.list.d/ngrok.list \
    && apt-get update \
    && apt-get install -y ngrok

# Configura e instala extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql gd

# Define diretório de trabalho
WORKDIR /var/www

# Copia arquivos da aplicação
COPY . .

# Instala dependências do composer
RUN composer install --no-dev --optimize-autoloader

# Copia script de entrada
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
