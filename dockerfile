# Production-Ready Laravel Dockerfile مع Apache
FROM php:8.2-apache

# تحديث النظام وتثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# تثبيت PHP extensions المطلوبة
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    pcntl \
    bcmath \
    zip \
    opcache

# تفعيل Apache modules
RUN a2enmod rewrite headers deflate expires

# تثبيت Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# إعداد Working Directory
WORKDIR /var/www/html

# نسخ composer files أولاً للاستفادة من Cache
COPY composer.json composer.lock ./

# تثبيت الـ dependencies فقط للإنتاج
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# نسخ باقي ملفات المشروع
COPY . .

# Composer autoload
RUN composer dump-autoload --optimize

# صلاحيات المجلدات المهمة
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Apache Virtual Host
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
        RewriteEngine On\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule ^ index.php [L]\n\
        Header always set X-Content-Type-Options "nosniff"\n\
        Header always set X-Frame-Options "DENY"\n\
        Header always set X-XSS-Protection "1; mode=block"\n\
        Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
    </Directory>\n\
    <Files ~ "^\\.(env|htaccess)$">\n\
        Require all denied\n\
    </Files>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# PHP إعدادات
RUN echo 'opcache.enable=1\n\
opcache.enable_cli=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=10000\n\
opcache.revalidate_freq=2\n\
opcache.fast_shutdown=1\n\
expose_php=Off\n\
display_errors=Off\n\
log_errors=On\n\
error_log=/var/log/apache2/php_errors.log\n\
memory_limit=512M\n\
upload_max_filesize=10M\n\
post_max_size=10M\n\
date.timezone=Africa/Cairo' > /usr/local/etc/php/conf.d/production.ini

# إنشاء ملف لوج
RUN mkdir -p /var/log/apache2 && \
    touch /var/log/apache2/php_errors.log && \
    chown www-data:www-data /var/log/apache2/php_errors.log

# متغيرات البيئة
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2

# فتح Port 80
EXPOSE 80

# Health Check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# تشغيل Laravel Optimization + Apache
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    apache2-foreground
