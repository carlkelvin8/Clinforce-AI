# Complete Setup & Deployment Guide

## Project Status: ✅ Production Ready

Your Clinforce AI application is fully configured and ready for production deployment on Railway.

## What's Included

### 1. Database
- ✅ 28 models with corresponding tables
- ✅ 36 migration files covering all tables
- ✅ SQLite support for local development
- ✅ MySQL support for production
- ✅ All relationships and constraints properly defined

### 2. Docker & Deployment
- ✅ Optimized Dockerfile using FrankenPHP
- ✅ Multi-stage build for smaller image size
- ✅ Railway configuration files
- ✅ Procfile for process management
- ✅ Environment templates for production

### 3. Code Quality
- ✅ Fixed Zoom Privacy & Filtering (not editable, always ON)
- ✅ Fixed SQLite/MySQL compatibility issues
- ✅ Production caching and optimization
- ✅ Health check endpoint
- ✅ Audit logging

### 4. Documentation
- ✅ DATABASE_SCHEMA.md - Complete database documentation
- ✅ DATABASE_OPERATIONS.md - Database operations guide
- ✅ RAILWAY_DEPLOYMENT.md - Deployment instructions
- ✅ RAILWAY_MYSQL_SETUP.md - MySQL setup guide
- ✅ PRODUCTION_CHECKLIST.md - Pre-deployment checklist
- ✅ DEPLOYMENT_SUMMARY.md - Quick overview

## Quick Start: Deploy to Railway in 5 Steps

### Step 1: Connect Repository
1. Go to https://railway.app/dashboard
2. Click "New Project"
3. Select "Deploy from GitHub"
4. Choose your `clinforce-ai` repository

### Step 2: Add MySQL Database
1. Click "Add Service" → "Database" → "MySQL"
2. Railway auto-populates database variables

### Step 3: Set Environment Variables
Go to your service's **Variables** tab and add:

```
# Application
APP_KEY=base64:YOUR_KEY_HERE
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.com
APP_FRONTEND_URL=https://your-railway-domain.com

# Database (auto-populated by Railway)
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}

# Mail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com

# Google OAuth
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://your-railway-domain.com/auth/google/callback

# Stripe
STRIPE_KEY=your-publishable-key
STRIPE_SECRET=your-secret-key
STRIPE_WEBHOOK_SECRET=your-webhook-secret

# OpenAI
OPENAI_API_KEY=your-api-key

# Zoom
ZOOM_ACCOUNT_ID=your-account-id
ZOOM_CLIENT_ID=your-client-id
ZOOM_CLIENT_SECRET=your-client-secret

# AWS S3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### Step 4: Deploy
1. Push code to GitHub
2. Railway automatically builds and deploys
3. Monitor in **Deployments** tab

### Step 5: Verify
Visit: `https://your-railway-domain.com/api/health`

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
3. Look for **Domains** section
4. URL format: `https://project-name-production.up.railway.app`

## Local Development Setup

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer
- MySQL or SQLite

### Installation
```bash
# Clone repository
git clone <your-repo-url>
cd clinforce-ai

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Create database
php artisan migrate:fresh

# Build frontend
npm run build

# Start development server
php artisan serve
```

### Development Commands
```bash
# Run migrations
php artisan migrate

# Fresh database
php artisan migrate:fresh

# Check health
curl http://localhost:8000/api/health

# Run tests
php artisan test

# Build frontend
npm run build

# Development mode
npm run dev
```

## Key Features

### Security
- ✅ Environment variables for all secrets
- ✅ HTTPS enforced in production
- ✅ CORS properly configured
- ✅ Rate limiting available
- ✅ Audit logging enabled

### Performance
- ✅ Config caching
- ✅ Route caching
- ✅ View caching
- ✅ Event caching
- ✅ Optimized autoloader
- ✅ Asset minification

### Monitoring
- ✅ Health check endpoint
- ✅ Error tracking ready
- ✅ Audit logs
- ✅ Application logs

### Database
- ✅ All tables created
- ✅ Proper relationships
- ✅ Foreign key constraints
- ✅ Indexes for performance
- ✅ Automatic migrations

## File Structure

```
clinforce-ai/
├── app/
│   ├── Models/              # 28 models
│   ├── Http/Controllers/    # API controllers
│   ├── Services/            # Business logic
│   └── ...
├── database/
│   ├── migrations/          # 36 migration files
│   └── seeders/             # Database seeders
├── resources/
│   ├── js/                  # Vue.js frontend
│   └── views/               # Blade templates
├── routes/
│   └── api.php              # API routes
├── Dockerfile               # Docker configuration
├── Procfile                 # Process definitions
├── railway.toml             # Railway config
├── .env.production          # Production template
├── .env.example             # Development template
└── Documentation files
    ├── DATABASE_SCHEMA.md
    ├── DATABASE_OPERATIONS.md
    ├── RAILWAY_DEPLOYMENT.md
    ├── PRODUCTION_CHECKLIST.md
    └── ...
```

## Troubleshooting

### Build Fails
- Check Docker logs
- Verify `composer.json` and `package.json`
- Ensure all dependencies are available

### Database Connection Error
- Verify MySQL service is running
- Check database credentials
- Ensure database exists

### Migrations Failed
- Check application logs
- Verify database user permissions
- Run manually: `railway run php artisan migrate --force`

### Health Check Shows db: false
- MySQL service may not be running
- Check database credentials
- Verify network connectivity

### Zoom Privacy & Filtering Still Shows
- Clear browser cache
- Verify frontend is rebuilt
- Check that you're viewing employer settings (not applicant)

## Next Steps

1. **Generate APP_KEY**
   ```bash
   php artisan key:generate
   ```

2. **Set Up MySQL on Railway**
   - Add MySQL service
   - Copy auto-populated variables

3. **Configure APIs**
   - Google OAuth
   - Stripe
   - OpenAI
   - Zoom

4. **Set Up Email**
   - Configure SMTP credentials
   - Test email sending

5. **Configure Storage**
   - Set up AWS S3 bucket
   - Add credentials

6. **Deploy**
   - Push to GitHub
   - Monitor deployment
   - Verify health endpoint

## Support Resources

- [Railway Documentation](https://docs.railway.app)
- [Laravel Documentation](https://laravel.com/docs)
- [FrankenPHP Documentation](https://frankenphp.dev)
- [Docker Documentation](https://docs.docker.com)
- [MySQL Documentation](https://dev.mysql.com/doc/)

## Deployment Checklist

Before going live, verify:

- [ ] All environment variables set
- [ ] MySQL database created
- [ ] APP_KEY generated
- [ ] API keys configured
- [ ] Email credentials set
- [ ] S3 bucket configured
- [ ] Health endpoint returns ok
- [ ] Database migrations ran
- [ ] Frontend builds successfully
- [ ] HTTPS enabled
- [ ] Custom domain configured (optional)
- [ ] Monitoring set up
- [ ] Backups configured
- [ ] Error tracking enabled

## Production Monitoring

### Health Check
```bash
curl https://your-domain.com/api/health
```

### View Logs
```bash
railway logs
```

### SSH into Container
```bash
railway shell
```

### Run Commands
```bash
railway run php artisan migrate:status
railway run php artisan tinker
```

## Maintenance

### Regular Tasks
- Monitor error logs
- Check database performance
- Review audit logs
- Update dependencies
- Test backups
- Monitor disk usage

### Backup Strategy
- Automatic MySQL backups (Railway)
- Regular database exports
- Code repository backups
- Document important configurations

## Performance Optimization

1. **Enable Redis** (optional)
   - Add Redis service
   - Set `CACHE_DRIVER=redis`

2. **Database Optimization**
   - Add indexes for frequently queried columns
   - Monitor slow queries
   - Optimize queries

3. **Asset Optimization**
   - Vite automatically minifies
   - Enable gzip compression
   - Use CDN for static files

4. **Queue Processing**
   - Set `QUEUE_CONNECTION=redis`
   - Monitor queue jobs

## Security Hardening

1. **Environment Variables**
   - Never commit secrets
   - Use Railway's variable management
   - Rotate keys regularly

2. **Database**
   - Use strong passwords
   - Limit user permissions
   - Enable SSL connections

3. **API**
   - Enable rate limiting
   - Validate all inputs
   - Use HTTPS only

4. **Monitoring**
   - Set up error tracking
   - Monitor suspicious activity
   - Review audit logs

## Summary

Your application is **production-ready** and can be deployed to Railway immediately. All database tables are created, migrations are in place, and the application is optimized for production use.

**Status**: ✅ Ready for Production Deployment

For detailed information, refer to the documentation files included in the project.
