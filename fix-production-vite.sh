#!/bin/bash

echo "🔧 Fixing Vite Production Issue..."
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please create .env file first."
    exit 1
fi

echo "📋 Current environment settings:"
echo "--------------------------------"
grep -E "APP_ENV|APP_DEBUG|VITE_DEV_SERVER_URL|VITE_API_URL" .env || echo "No Vite settings found"
echo ""

# Backup .env
echo "💾 Creating backup of .env..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Check and fix APP_ENV
if grep -q "^APP_ENV=local" .env; then
    echo "🔄 Changing APP_ENV from local to production..."
    sed -i 's/^APP_ENV=local/APP_ENV=production/' .env
elif grep -q "^APP_ENV=production" .env; then
    echo "✅ APP_ENV is already set to production"
else
    echo "⚠️  APP_ENV not found, adding it..."
    echo "APP_ENV=production" >> .env
fi

# Check and fix APP_DEBUG
if grep -q "^APP_DEBUG=true" .env; then
    echo "🔄 Changing APP_DEBUG from true to false..."
    sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
elif grep -q "^APP_DEBUG=false" .env; then
    echo "✅ APP_DEBUG is already set to false"
else
    echo "⚠️  APP_DEBUG not found, adding it..."
    echo "APP_DEBUG=false" >> .env
fi

# Remove VITE_DEV_SERVER_URL if it exists
if grep -q "^VITE_DEV_SERVER_URL=" .env; then
    echo "🗑️  Removing VITE_DEV_SERVER_URL..."
    sed -i '/^VITE_DEV_SERVER_URL=/d' .env
else
    echo "✅ VITE_DEV_SERVER_URL not found (good!)"
fi

# Check VITE_API_URL
if grep -q "^VITE_API_URL=http://localhost" .env; then
    echo "⚠️  WARNING: VITE_API_URL is still pointing to localhost!"
    echo "   You need to update it manually to: https://app.aiclinforce.com/api"
    echo ""
    read -p "Do you want me to update it now? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sed -i 's|^VITE_API_URL=.*|VITE_API_URL=https://app.aiclinforce.com/api|' .env
        echo "✅ Updated VITE_API_URL"
    fi
fi

echo ""
echo "📋 Updated environment settings:"
echo "--------------------------------"
grep -E "APP_ENV|APP_DEBUG|VITE_API_URL" .env
echo ""

# Check if public/build exists
if [ ! -d "public/build" ]; then
    echo "⚠️  WARNING: public/build directory not found!"
    echo "   Running npm run build..."
    npm run build
else
    echo "✅ public/build directory exists"
    
    # Check if manifest.json exists
    if [ ! -f "public/build/manifest.json" ]; then
        echo "⚠️  WARNING: public/build/manifest.json not found!"
        echo "   Running npm run build..."
        npm run build
    else
        echo "✅ public/build/manifest.json exists"
    fi
fi

echo ""
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo ""
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Done! Your production site should now load correctly."
echo ""
echo "🔍 To verify:"
echo "1. Open https://app.aiclinforce.com in your browser"
echo "2. Press F12 to open DevTools"
echo "3. Go to Network tab"
echo "4. Refresh the page"
echo "5. Check that assets load from /build/assets/ NOT from [::1]:5173"
echo ""
echo "If you still see the error, try:"
echo "- Restart your web server (Apache/Nginx)"
echo "- Clear your browser cache (Ctrl+Shift+Delete)"
echo "- Check that your .env file is correct: cat .env | grep -E 'APP_ENV|VITE'"
