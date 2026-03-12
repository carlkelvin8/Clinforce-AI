FROM heroku/heroku:22-build as build

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Build frontend assets
RUN npm ci && npm run build

# Final stage
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install required extensions
RUN apk add --no-cache \
    nginx \
    curl \
    mysql-client \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql mbstring

# Copy from build stage
COPY --from=build /app /app

# Copy nginx config
RUN mkdir -p /etc/nginx/conf.d && \
    echo 'server { \
        listen 80; \
        server_name _; \
        root /app/public; \
        index index.php; \
        client_max_body_size 100M; \
        location / { \
            try_files $uri $uri/ /index.php?$query_string; \
        } \
        location ~ \.php$ { \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_index index.php; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
            include fastcgi_params; \
            fastcgi_read_timeout 60; \
        } \
    }' > /etc/nginx/conf.d/default.conf

# Create supervisor config
RUN mkdir -p /etc/supervisor/conf.d && \
    echo '[supervisord] \
nodaemon=true \
\
[program:php-fpm] \
command=/usr/local/sbin/php-fpm \
autostart=true \
autorestart=true \
stderr_logfile=/var/log/php-fpm.err.log \
stdout_logfile=/var/log/php-fpm.out.log \
\
[program:nginx] \
command=/usr/sbin/nginx -g "daemon off;" \
autostart=true \
autorestart=true \
stderr_logfile=/var/log/nginx.err.log \
stdout_logfile=/var/log/nginx.out.log' > /etc/supervisor/conf.d/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 755 /app/storage /app/bootstrap/cache

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

