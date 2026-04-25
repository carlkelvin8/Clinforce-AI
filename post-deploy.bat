@echo off
echo Starting deployment process...

echo Creating storage directories...
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\logs" mkdir "storage\logs"

echo Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo Deployment complete!
pause
