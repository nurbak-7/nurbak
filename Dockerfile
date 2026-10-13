# NURBAK — PHP 8.2 + Apache
# Compatible con PDO/MySQL (TiDB Cloud), GD, ZIP y PhpSpreadsheet.
FROM php:8.2-apache

# Instalar dependencias nativas y extensiones PHP que no incluye la imagen base.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl; \
    rm -rf /var/lib/apt/lists/*

# Valores PHP de producción.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'display_errors=Off' \
        'display_startup_errors=Off' \
        'log_errors=On' \
        'expose_php=Off' \
        > "$PHP_INI_DIR/conf.d/zz-nurbak.ini"

# Hacer que Apache aplique los .htaccess existentes del proyecto.
# Son importantes para bloquear el acceso web directo a confi/, datos/, contr/ y libre/.
# También se desactivan los listados de directorio.
RUN printf '%s\n' \
        '<Directory /var/www/html>' \
        '    Options -Indexes +FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/nurbak-directory.conf \
    && a2enconf nurbak-directory

# El contexto de construcción debe ser la raíz del repositorio NURBAK.
COPY . /var/www/html/

# Validar durante la construcción que las extensiones necesarias están disponibles.
# Si falta alguna, la imagen debe fallar al construirse y no desplegar una web incompleta.
RUN php -r '$need = ["pdo_mysql", "gd", "zip", "intl", "curl", "mbstring", "dom", "simplexml", "xml", "xmlreader", "xmlwriter", "fileinfo"]; foreach ($need as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Falta la extensión PHP: {$ext}" . PHP_EOL); exit(1); } } if (!in_array("mysql", PDO::getAvailableDrivers(), true)) { fwrite(STDERR, "El driver PDO MySQL no está disponible" . PHP_EOL); exit(1); }' \
    && test -f /var/www/html/libre/compo/vendor/autoload.php \
    && apache2ctl -t

EXPOSE 80
