FROM php:8.2-cli



# Instala curl, unzip e wget (necessários)
RUN apt-get update && apt-get install -y curl unzip wget gnupg

# Instala ngrok (sem usar sudo)
RUN curl -sSL https://ngrok-agent.s3.amazonaws.com/ngrok.asc \
| tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null \
&& echo "deb https://ngrok-agent.s3.amazonaws.com buster main" \
| tee /etc/apt/sources.list.d/ngrok.list \
&& apt-get update \
  && apt-get install -y ngrok
  
RUN docker-php-ext-install pdo pdo_mysql

# Define diretório de trabalho
WORKDIR /var/www

# Copia script de entrada
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
