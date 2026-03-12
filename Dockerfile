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
FROM dunglas/frankenphp:latest-alpine

WORKDIR /app

# Copy from build stage
COPY --from=build /app /app

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port
EXPOSE 80

# Start FrankenPHP
CMD ["frankenphp", "run", "--bind=0.0.0.0:80"]
