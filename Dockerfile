
# Utiliza a imagem oficial do PHP 8.3 com Apache
FROM php:8.3-apache

# Copia o executável do Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Instala as extensões necessárias para trabalhar com MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Instala e habilita o Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Habilita o módulo rewrite do Apache
RUN a2enmod rewrite

# Configura o diretório público da aplicação
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Atualiza o DocumentRoot nas configurações do Apache
RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Copia a configuração do Xdebug
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

# Define o diretório de trabalho da aplicação
WORKDIR /var/www/html
# Instala dependências do sistema necessárias para o Composer descompactar pacotes
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli zip

