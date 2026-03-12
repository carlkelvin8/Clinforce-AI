# Railway Deployment Guide

This guide walks you through deploying Clinforce AI to Railway with MySQL database.

## Prerequisites

- Railway account (https://railway.app)
- GitHub repository connected to Railway
- Environment variables configured

## Quick Start

### 1. Connect Repository to Railway

1. Go to [Railway Dashboard](https://railway.app/dashboard)
2. Click "New Project"
3. Select "Deploy from GitHub"
4. Authorize Railway to access your GitHub account
5. Select the `clinforce-ai` repository

### 2. Add MySQL Database

1. Click **Add Service** → **Database** → **MySQL**
2. Railway automatically creates MySQL and sets environment variables
3. Variables are auto-populated: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

### 3. Configure Environment Variables

Go to your service's **Variables** tab and add:

#### Database (Auto-populated by Railway)
```
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
```

#### Application
```
APP_KEY=base64:YOUR_KEY_HERE
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.com
APP_FRONTEND_URL=https://your-railway-domain.com
```

#### Mail (SMTP)
```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
```

#### Google OAuth
```
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://your-railway-domain.com/auth/google/callback
```

#### Stripe
```
STRIPE_KEY=your-publishable-key
STRIPE_SECRET=your-secret-key
STRIPE_WEBHOOK_SECRET=your-webhook-secret
```

#### OpenAI
```
OPENAI_API_KEY=your-api-key
```

#### Zoom
```
ZOOM_ACCOUNT_ID=your-account-id
ZOOM_CLIENT_ID=your-client-id
ZOOM_CLIENT_SECRET=your-client-secret
```

#### AWS S3 (for file storage)
```
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### 4. Deploy

1. Push code to GitHub
2. Railway automatically builds and deploys
3. Monitor in **Deployments** tab
4. Check **Logs** for any errors

### 5. Verify Deployment

Test the health endpoint:
```
https://your-railway-domain.com/api/health
```

Should return:
```json
{
  "status": "ok",
  "checks": {
    "app": true,
    "db": true
  },
  "time": "2026-03-12T08:00:00Z"
}
```

## Finding Your Railway URL

1. Go to Railway Dashboard
2. Click your service
3. Look for **Domains** section in the right panel
4. URL format: `https://project-name-production.up.railway.app`

## Troubleshooting

### Build Fails
- Check logs for missing dependencies
- Verify `composer.json` and `package.json` are valid
- Ensure Node.js and PHP versions are compatible

### Database Connection Error
- Verify MySQL service is running
- Check `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`
- Ensure database exists

### Migrations Failed
- Check logs for specific error
- Verify database user has proper permissions
- Run manually: `railway run php artisan migrate --force`

### Health Check Shows db: false
- MySQL service may not be running
- Check database credentials
- Verify network connectivity

## Performance Optimization

1. **Caching**: Set `CACHE_DRIVER=redis` (add Redis service)
2. **Sessions**: Set `SESSION_DRIVER=cookie`
3. **File Storage**: Use S3 with `FILESYSTEM_DISK=s3`
4. **Queue**: Set `QUEUE_CONNECTION=redis` (add Redis service)

## Monitoring

1. **Logs**: Check application logs in Railway dashboard
2. **Health**: Monitor `/api/health` endpoint
3. **Performance**: Use Railway's metrics dashboard
4. **Errors**: Set up error tracking (Sentry, etc.)

## Rollback

To rollback to a previous deployment:
1. Go to **Deployments** tab
2. Click the deployment to rollback to
3. Click **Redeploy**

## Support

- Railway Docs: https://docs.railway.app
- Laravel Docs: https://laravel.com/docs
- For issues, check Railway logs and application logs
