# Utiliza a imagem oficial do PHP 8.3 com Apache
FROM php:8.3-apache

# Copia o executável do Composer da imagem oficial do Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Instala as extensões PHP necessárias para trabalhar com MySQL
# PDO permite acessar bancos de dados usando uma interface comum
# pdo_mysql permite conectar ao MySQL por meio do PDO
# mysqli é outra extensão para conexão com MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Instala o Xdebug e habilita a extensão no PHP
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # Habilita o módulo rewrite do Apache, útil para URLs amigáveis
    && a2enmod rewrite

# Copia a configuração personalizada do Xdebug para o PHP
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

# Define o diretório de trabalho da aplicação dentro do container
WORKDIR /var/www/html