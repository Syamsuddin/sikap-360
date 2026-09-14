FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
 && docker-php-ext-install pdo_mysql mbstring \
 && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html/public>\nOptions -Indexes\nAllowOverride None\nRequire all granted\nDirectoryIndex index.php\n</Directory>\n<Files "index.html">\nRequire all denied\n</Files>\n' > /etc/apache2/conf-available/sikap.conf \
 && a2enconf sikap
COPY . /var/www/html/
RUN printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\nsession.cookie_httponly=1\nsession.use_strict_mode=1\nsession.gc_maxlifetime=1800\n' > /usr/local/etc/php/conf.d/sikap.ini
