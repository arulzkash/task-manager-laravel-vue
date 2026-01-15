# 1. Gunakan Image PHP Official
FROM php:8.2-cli

# 2. Install Library System yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl

# 3. Install Node.js & NPM (Supaya bisa build Vue)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 4. Set Folder Kerja
WORKDIR /var/www/html

# 5. Copy semua file project ke dalam Docker
COPY . .

# 6. Install Composer (PHP Dependencies)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 7. Build Aset Vue (Frontend)
RUN npm install && npm run build

# 8. Set Permission folder storage (PENTING!)
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# 9. Expose Port (Render biasanya pakai 10000)
ENV PORT=10000
EXPOSE 10000

# 10. Perintah Menjalankan Aplikasi (Migrate dulu, baru Serve)
CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000"