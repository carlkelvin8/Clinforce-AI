# Railway MySQL Setup Guide

## Step 1: Add MySQL Database to Railway

1. Go to your Railway project dashboard: https://railway.app/dashboard
2. Click **Add Service** button
3. Select **Database** → **MySQL**
4. Railway will automatically create a MySQL instance and populate environment variables

## Step 2: Verify MySQL Environment Variables

After adding MySQL, Railway automatically sets these variables:
- `MYSQL_HOST` - Database host
- `MYSQL_PORT` - Database port (usually 3306)
- `MYSQL_DATABASE` - Database name
- `MYSQL_USER` - Database user
- `MYSQL_PASSWORD` - Database password

You can view these in your service's **Variables** tab.

## Step 3: Configure Your App Variables

In your Railway project, go to the **Variables** tab and ensure these are set:

```
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
```

## Step 4: Set Other Required Variables

Add these critical variables:

```
APP_KEY=base64:YOUR_KEY_HERE
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.com
APP_FRONTEND_URL=https://your-railway-domain.com

MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email

GOOGLE_CLIENT_ID=your-id
GOOGLE_CLIENT_SECRET=your-secret
GOOGLE_REDIRECT_URI=https://your-railway-domain.com/auth/google/callback

STRIPE_KEY=your-key
STRIPE_SECRET=your-secret
STRIPE_WEBHOOK_SECRET=your-webhook-secret

OPENAI_API_KEY=your-key

ZOOM_ACCOUNT_ID=your-id
ZOOM_CLIENT_ID=your-id
ZOOM_CLIENT_SECRET=your-secret

AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

## Step 5: Deploy

1. Push your code to GitHub
2. Railway will automatically build and deploy
3. Monitor the deployment in the **Deployments** tab
4. Check logs for any errors

## Step 6: Verify Deployment

Once deployed, test:

1. **Health Check**: Visit `https://your-railway-domain.com/api/health`
   - Should return: `{"status":"ok","checks":{"app":true,"db":true},...}`

2. **Database Connection**: If health check shows `"db":true`, MySQL is connected

3. **Migrations**: Check logs to confirm migrations ran successfully

## Troubleshooting

### MySQL Connection Error
- Verify `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD` are correct
- Check MySQL service is running in Railway
- Ensure database exists

### Migrations Failed
- Check logs for specific error
- Verify database user has proper permissions
- Try running migrations manually: `railway run php artisan migrate --force`

### Health Check Fails
- Visit `/api/health` endpoint
- Check if `"db":false` indicates database issue
- Review application logs

## Useful Railway Commands

```bash
# View logs
railway logs

# Run artisan command
railway run php artisan migrate --force

# SSH into container
railway shell

# View environment variables
railway variables
```

## Next Steps

1. Configure custom domain (optional)
2. Set up monitoring and alerts
3. Configure backups for MySQL
4. Set up error tracking (Sentry, etc.)
5. Monitor performance metrics
