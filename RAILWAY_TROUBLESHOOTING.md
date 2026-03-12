# Railway Deployment Troubleshooting

## Health Check Failed Issue

### Problem
```
Healthcheck failed!
Path: /health
Retry window: 10s
1/1 replicas never became healthy!
```

### Solution Applied
Updated Docker configuration to use PHP-FPM + Nginx instead of FrankenPHP for better compatibility with Railway.

### Changes Made
1. **Dockerfile** - Now uses `php:8.2-fpm-alpine` with Nginx
2. **Procfile** - Updated start command to run both PHP-FPM and Nginx
3. **railway.toml** - Updated health check path to `/api/health`

### How to Verify

#### 1. Check Deployment Logs
```bash
railway logs
```

Look for:
- PHP-FPM starting successfully
- Nginx starting successfully
- No error messages

#### 2. Test Health Endpoint
Once deployed, visit:
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

#### 3. SSH into Container
```bash
railway shell
```

Then test locally:
```bash
curl http://localhost/api/health
```

### Common Issues & Solutions

#### Issue: Still Getting Health Check Failures

**Solution 1: Check Database Connection**
```bash
railway shell
php artisan tinker
>>> DB::connection()->getPdo()
```

If this fails, verify MySQL service is running and credentials are correct.

**Solution 2: Check Nginx Configuration**
```bash
railway shell
nginx -t
```

Should output: `nginx: configuration file test is successful`

**Solution 3: Check PHP-FPM Status**
```bash
railway shell
ps aux | grep php-fpm
```

Should show PHP-FPM processes running.

#### Issue: 502 Bad Gateway

**Cause**: PHP-FPM not responding to Nginx

**Solution**:
1. Check PHP-FPM is running: `ps aux | grep php-fpm`
2. Check socket/port: `netstat -tlnp | grep 9000`
3. Check Nginx error logs: `tail -f /var/log/nginx/error.log`

#### Issue: 503 Service Unavailable

**Cause**: Application error or database connection issue

**Solution**:
1. Check application logs: `railway logs`
2. Check database connection: `php artisan tinker` → `DB::connection()->getPdo()`
3. Check migrations ran: `php artisan migrate:status`

#### Issue: Timeout on Health Check

**Cause**: Application taking too long to respond

**Solution**:
1. Increase health check timeout in `railway.toml`:
```toml
[deploy]
healthcheckTimeout = 30
```

2. Check for slow queries:
```bash
railway shell
php artisan tinker
>>> DB::enableQueryLog()
>>> // Run some queries
>>> DB::getQueryLog()
```

### Deployment Process

1. **Build Phase** (Docker build)
   - Installs dependencies
   - Builds frontend assets
   - Caches configuration

2. **Release Phase** (Procfile release command)
   - Runs migrations: `php artisan migrate --force`
   - Creates database tables

3. **Start Phase** (Procfile web command)
   - Starts PHP-FPM
   - Starts Nginx
   - Listens on port 80

4. **Health Check Phase**
   - Waits for `/api/health` to respond
   - Retries for 10 seconds
   - If successful, deployment completes

### Debugging Steps

#### Step 1: Check Build Logs
```bash
railway logs --service=<service-name>
```

Look for:
- Composer install success
- NPM build success
- No PHP errors

#### Step 2: Check Release Logs
```bash
railway logs --service=<service-name>
```

Look for:
- Migrations running
- "Migrated" messages
- No SQL errors

#### Step 3: Check Start Logs
```bash
railway logs --service=<service-name>
```

Look for:
- PHP-FPM starting
- Nginx starting
- No error messages

#### Step 4: Manual Health Check
```bash
railway shell
curl -v http://localhost/api/health
```

Should show:
- HTTP/1.1 200 OK
- JSON response with status: ok

### Environment Variables to Check

Ensure these are set in Railway:
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
```

### Performance Optimization

If health check is slow:

1. **Enable Query Caching**
```bash
railway shell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Check Database Performance**
```bash
railway shell
php artisan tinker
>>> DB::statement('SHOW PROCESSLIST')
```

3. **Monitor Resource Usage**
- Check Railway dashboard for CPU/Memory usage
- Consider upgrading instance if needed

### Rollback

If deployment fails:

1. Go to Railway Dashboard
2. Click **Deployments** tab
3. Select previous successful deployment
4. Click **Redeploy**

### Getting Help

If issues persist:

1. **Check Railway Docs**: https://docs.railway.app
2. **Check Laravel Docs**: https://laravel.com/docs
3. **Review Logs**: `railway logs --follow`
4. **SSH into Container**: `railway shell`

### Quick Checklist

- [ ] MySQL service is running
- [ ] Database credentials are correct
- [ ] Migrations have run successfully
- [ ] Health endpoint returns 200 OK
- [ ] No PHP errors in logs
- [ ] No Nginx errors in logs
- [ ] Database connection is working
- [ ] All environment variables are set

### Next Steps

Once health check passes:

1. Verify all API endpoints work
2. Test authentication flow
3. Test file uploads
4. Test payment processing
5. Monitor logs for errors
6. Set up error tracking (Sentry)
7. Configure monitoring alerts

---

**Status**: Deployment configuration updated and pushed to GitHub. Railway will rebuild on next push.
