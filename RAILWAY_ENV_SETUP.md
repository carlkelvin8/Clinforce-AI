# Railway Backend Environment Variables Setup

## Overview

Your Laravel backend on Railway needs environment variables configured. These are set in the Railway dashboard, not in code.

## Step 1: Go to Railway Dashboard

1. Visit: https://railway.app/dashboard
2. Select your project
3. Click on your service (the Laravel API)
4. Go to **Variables** tab

## Step 2: Set Required Variables

### Application Variables

```
APP_NAME=clinforce-ai
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.com
APP_FRONTEND_URL=https://your-hostinger-domain.com
```

**Replace:**
- `your-railway-domain.com` - Your Railway API domain
- `your-hostinger-domain.com` - Your Hostinger Vue domain

### Generate APP_KEY

Run this locally:
```bash
php artisan key:generate
```

Copy the output (looks like `base64:xxxxx...`) and set:
```
APP_KEY=base64:xxxxx...
```

### Database Variables

These are **auto-populated by Railway** when you add MySQL service:

```
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
```

**Do NOT change these** - Railway fills them automatically.

### Session & Cache

```
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
CACHE_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=clinforce_
```

### Mail Configuration

Set your SMTP credentials:

```
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=Clinforce
```

**For Gmail:**
1. Enable 2-factor authentication
2. Generate app password: https://myaccount.google.com/apppasswords
3. Use the 16-character password

### Google OAuth

```
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
```

Get from: https://console.cloud.google.com

### Stripe

```
STRIPE_KEY=pk_test_xxxxx...
STRIPE_SECRET=sk_test_xxxxx...
STRIPE_WEBHOOK_SECRET=whsec_xxxxx...
VITE_STRIPE_PUBLISHABLE_KEY=pk_test_xxxxx...
```

Get from: https://dashboard.stripe.com/apikeys

### OpenAI

```
OPENAI_API_KEY=sk-proj-xxxxx...
```

Get from: https://platform.openai.com/api-keys

### Zoom

```
ZOOM_ACCOUNT_ID=your-zoom-account-id
ZOOM_CLIENT_ID=your-zoom-client-id
ZOOM_CLIENT_SECRET=your-zoom-client-secret
ZOOM_TIMEZONE=Asia/Manila
```

Get from: https://marketplace.zoom.us/develop/apps

### AWS S3 (for file storage)

```
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
FILESYSTEM_DISK=s3
```

Get from: https://console.aws.amazon.com

### Redis (if using Redis service)

These are **auto-populated by Railway** when you add Redis service:

```
REDIS_HOST=${{REDIS_HOST}}
REDIS_PASSWORD=${{REDIS_PASSWORD}}
REDIS_PORT=${{REDIS_PORT}}
QUEUE_CONNECTION=redis
```

## Step 3: Add Services on Railway

### Add MySQL Database

1. Click **Add Service** → **Database** → **MySQL**
2. Railway auto-populates `MYSQL_*` variables
3. Database is ready

### Add Redis (Optional but Recommended)

1. Click **Add Service** → **Database** → **Redis**
2. Railway auto-populates `REDIS_*` variables
3. Cache and queue processing enabled

## Step 4: Configure CORS for Frontend

Update `config/cors.php` in your Laravel project:

```php
'allowed_origins' => [
    'https://your-hostinger-domain.com',
    'https://www.your-hostinger-domain.com',
    'http://localhost:5173', // for local development
],
```

Then push to GitHub so Railway redeploys.

## Step 5: Verify Variables

### Check in Railway Dashboard

1. Go to **Variables** tab
2. Verify all variables are set
3. Look for any with empty values (red warning)

### Test Connection

```bash
railway shell
php artisan tinker
>>> DB::connection()->getPdo()
>>> // Should not throw error
```

## Complete Checklist

### Application
- [ ] APP_NAME set
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_KEY generated and set
- [ ] APP_URL set to Railway domain
- [ ] APP_FRONTEND_URL set to Hostinger domain

### Database
- [ ] MySQL service added
- [ ] DB_* variables auto-populated
- [ ] Migrations ran successfully

### Mail
- [ ] MAIL_MAILER=smtp
- [ ] MAIL_HOST set
- [ ] MAIL_USERNAME set
- [ ] MAIL_PASSWORD set
- [ ] MAIL_FROM_ADDRESS set

### APIs
- [ ] GOOGLE_CLIENT_ID set
- [ ] GOOGLE_CLIENT_SECRET set
- [ ] STRIPE_KEY set
- [ ] STRIPE_SECRET set
- [ ] OPENAI_API_KEY set
- [ ] ZOOM_* variables set

### Storage
- [ ] AWS_ACCESS_KEY_ID set
- [ ] AWS_SECRET_ACCESS_KEY set
- [ ] AWS_BUCKET set
- [ ] FILESYSTEM_DISK=s3

### Cache & Queue (Optional)
- [ ] Redis service added (if using)
- [ ] REDIS_* variables auto-populated
- [ ] CACHE_DRIVER=redis
- [ ] QUEUE_CONNECTION=redis

## Troubleshooting

### Issue: Database Connection Error
```
SQLSTATE[HY000]: General error: 1 near "MODIFY": syntax error
```

**Solution**: Migrations already fixed. Just ensure MySQL service is running.

### Issue: CORS Error
```
Access to XMLHttpRequest blocked by CORS policy
```

**Solution**: 
1. Update `config/cors.php`
2. Add your Hostinger domain
3. Push to GitHub
4. Wait for Railway to redeploy

### Issue: Mail Not Sending
```
Connection refused
```

**Solution**:
1. Verify MAIL_HOST is correct
2. Check MAIL_USERNAME and MAIL_PASSWORD
3. For Gmail, use app password (not regular password)
4. Enable "Less secure app access" if needed

### Issue: API Key Invalid
```
Unauthorized
```

**Solution**:
1. Verify API key is correct
2. Check for extra spaces or quotes
3. Regenerate key if needed
4. Redeploy after updating

## Environment Variables Summary

| Variable | Type | Required | Example |
|----------|------|----------|---------|
| APP_KEY | String | Yes | base64:xxxxx... |
| APP_URL | URL | Yes | https://api.example.com |
| APP_FRONTEND_URL | URL | Yes | https://example.com |
| DB_HOST | String | Auto | ${{MYSQL_HOST}} |
| MAIL_HOST | String | Yes | smtp.gmail.com |
| GOOGLE_CLIENT_ID | String | Yes | xxxxx.apps.googleusercontent.com |
| STRIPE_KEY | String | Yes | pk_test_xxxxx |
| OPENAI_API_KEY | String | Yes | sk-proj-xxxxx |
| AWS_BUCKET | String | Yes | my-bucket-name |
| ZOOM_CLIENT_ID | String | Yes | xxxxx |

## Next Steps

1. ✅ Add MySQL service
2. → Set all required variables
3. → Add Redis service (optional)
4. → Update CORS configuration
5. → Push to GitHub
6. → Wait for Railway to redeploy
7. → Test API endpoints
8. → Verify frontend can connect

## Support

- Railway Docs: https://docs.railway.app
- Laravel Docs: https://laravel.com/docs
- Environment Variables: https://laravel.com/docs/configuration

---

**All backend environment variables are configured on Railway, not in code!**
