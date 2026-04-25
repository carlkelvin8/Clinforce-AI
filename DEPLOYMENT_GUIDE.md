# Production Deployment Guide for Hostinger

## Issue: Vite CORS Error (Loading Dev Server in Production)

If you see this error in production:
```
Access to script at 'http://[::1]:5173/@vite/client' from origin 'https://app.aiclinforce.com' 
has been blocked by CORS policy
```

This means Laravel is trying to load the Vite dev server instead of built assets.

## Root Cause

Laravel's Vite plugin automatically detects if the dev server is running by checking:
1. If `VITE_DEV_SERVER_URL` is set in `.env`
2. If the dev server is accessible at the default port (5173)
3. If `APP_ENV` is not set to `production`

## Solution: Step-by-Step Deployment

### Step 1: Update .env File on Hostinger Server

SSH into your Hostinger server and edit the `.env` file:

```bash
nano .env
```

**CRITICAL SETTINGS:**

```env
# Set environment to production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.aiclinforce.com

# DO NOT include VITE_DEV_SERVER_URL
# Remove this line if it exists: VITE_DEV_SERVER_URL=http://localhost:5173

# Set correct API URL for frontend
VITE_API_URL=https://app.aiclinforce.com/api

# Update database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_database_name
DB_USERNAME=your_hostinger_database_user
DB_PASSWORD=your_hostinger_database_password

# Update mail settings (use Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your_email@aiclinforce.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@aiclinforce.com"
MAIL_FROM_NAME="ClinForce AI"

# Update Stripe keys (production keys)
STRIPE_KEY=pk_live_xxxxx
STRIPE_SECRET=sk_live_xxxxx
VITE_STRIPE_PUBLISHABLE_KEY=pk_live_xxxxx

# Update Google OAuth (production credentials)
GOOGLE_CLIENT_ID=your_production_client_id
GOOGLE_CLIENT_SECRET=your_production_client_secret
GOOGLE_REDIRECT_URI=https://app.aiclinforce.com/api/auth/google/callback

# Frontend URL
APP_FRONTEND_URL=https://app.aiclinforce.com
SANCTUM_STATEFUL_DOMAINS=app.aiclinforce.com
```

Save and exit (Ctrl+X, then Y, then Enter).

### Step 2: Pull Latest Code

```bash
cd /path/to/your/app
git pull origin feature/apr25-updates
```

### Step 3: Run Deployment Script

```bash
chmod +x post-deploy.sh
./post-deploy.sh
```

This script will:
- Install/update Composer dependencies
- Install/update NPM dependencies
- Build frontend assets (creates `public/build/` directory)
- Run database migrations
- Clear all Laravel caches
- Optimize Laravel for production

### Step 4: Verify Built Assets

Check that the build directory exists and contains files:

```bash
ls -la public/build/
```

You should see:
- `manifest.json` - Maps source files to built files
- `assets/` directory with `.js` and `.css` files

### Step 5: Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize
```

### Step 6: Set Correct Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

(Replace `www-data` with your web server user if different)

### Step 7: Verify in Browser

1. Open https://app.aiclinforce.com
2. Open browser DevTools (F12) → Network tab
3. Refresh the page
4. Check that assets are loaded from `/build/assets/` NOT from `[::1]:5173`

**Correct URLs should look like:**
```
https://app.aiclinforce.com/build/assets/app-abc123.js
https://app.aiclinforce.com/build/assets/app-xyz789.css
```

**Incorrect URLs (the problem):**
```
http://[::1]:5173/@vite/client
http://[::1]:5173/resources/js/app.js
```

## Troubleshooting

### Problem: Still Loading Dev Server

**Check 1: Verify .env settings**
```bash
grep -E "APP_ENV|VITE_DEV_SERVER_URL" .env
```

Should show:
```
APP_ENV=production
```

Should NOT show `VITE_DEV_SERVER_URL` at all.

**Check 2: Clear config cache**
```bash
php artisan config:clear
php artisan config:cache
```

**Check 3: Verify manifest exists**
```bash
cat public/build/manifest.json
```

If this file doesn't exist, run:
```bash
npm run build
```

**Check 4: Check blade template**
```bash
grep -A 2 "@vite" resources/views/app.blade.php
```

Should show:
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Problem: 404 Errors on Assets

**Check web server configuration:**

For Apache (`.htaccess` in public directory):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Don't rewrite files or directories
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Rewrite everything else to index.php
    RewriteRule ^ index.php [L]
</IfModule>
```

For Nginx:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Problem: Session/Storage Errors

Create missing directories:
```bash
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/logs
chmod -R 755 storage
```

## Quick Reference: Deployment Checklist

- [ ] Update `.env` file with production settings
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Remove or leave empty `VITE_DEV_SERVER_URL`
- [ ] Update `VITE_API_URL=https://app.aiclinforce.com/api`
- [ ] Pull latest code from Git
- [ ] Run `./post-deploy.sh`
- [ ] Verify `public/build/manifest.json` exists
- [ ] Clear all Laravel caches
- [ ] Set correct file permissions
- [ ] Test in browser (check Network tab)

## Local Development vs Production

### Local Development (.env)
```env
APP_ENV=local
APP_DEBUG=true
VITE_API_URL=http://localhost:8000/api
# Vite dev server runs on port 5173
```

Run: `npm run dev` (starts Vite dev server)

### Production (.env)
```env
APP_ENV=production
APP_DEBUG=false
VITE_API_URL=https://app.aiclinforce.com/api
# No VITE_DEV_SERVER_URL
```

Run: `npm run build` (creates static assets in public/build/)

## Support

If you continue to have issues:

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check web server error logs
3. Verify all environment variables: `php artisan config:show`
4. Test API endpoint: `curl https://app.aiclinforce.com/api/health`

## Additional Notes

- The `@vite` directive in `app.blade.php` automatically switches between dev and production modes
- In production, it reads from `public/build/manifest.json` to load the correct hashed filenames
- In development, it connects to the Vite dev server for hot module replacement
- Never commit `.env` file to Git (it's in `.gitignore`)
- Use `.env.production` as a reference template only
