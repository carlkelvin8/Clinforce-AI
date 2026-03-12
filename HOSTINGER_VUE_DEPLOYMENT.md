# Deploy Vue Frontend to Hostinger

## Prerequisites

- Hostinger account with hosting plan
- FTP/SFTP access credentials
- Domain name (or use Hostinger's subdomain)
- Node.js 18+ installed locally

## Step 1: Build Vue Application

### 1.1 Build for Production
```bash
# Navigate to project root
cd clinforce-ai

# Install dependencies (if not already done)
npm install

# Build for production
npm run build
```

This creates a `dist/` folder with optimized production files.

### 1.2 Verify Build
```bash
# Check dist folder was created
ls -la dist/

# Should contain:
# - index.html
# - assets/ (with .js and .css files)
# - favicon.ico (if present)
```

## Step 2: Configure for Hostinger

### 2.1 Update API Base URL

Create a `.env.production` file in your project root:

```env
VITE_API_URL=https://your-railway-api-domain.com/api
```

Or update `vite.config.js`:

```javascript
export default defineConfig({
  plugins: [vue()],
  define: {
    __API_URL__: JSON.stringify(process.env.VITE_API_URL || 'https://your-railway-api-domain.com/api')
  }
})
```

### 2.2 Update API Calls

In your API client (`resources/js/lib/api.js`), ensure it uses the correct base URL:

```javascript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://your-railway-api-domain.com/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
})

export default api
```

### 2.3 Configure CORS

Your Laravel API on Railway needs to allow requests from your Hostinger domain.

Update `config/cors.php`:

```php
'allowed_origins' => [
    'https://your-hostinger-domain.com',
    'https://www.your-hostinger-domain.com',
    'http://localhost:5173', // for local development
],
```

Then push to GitHub so Railway redeploys with CORS enabled.

## Step 3: Connect to Hostinger via FTP

### 3.1 Get FTP Credentials

1. Log in to Hostinger Control Panel
2. Go to **Files** → **FTP Accounts**
3. Create new FTP account or use existing
4. Note down:
   - FTP Host
   - FTP Username
   - FTP Password
   - FTP Port (usually 21 or 22 for SFTP)

### 3.2 Connect via FTP Client

**Option A: Using FileZilla (Recommended)**

1. Download FileZilla: https://filezilla-project.org/
2. Open FileZilla
3. Go to **File** → **Site Manager**
4. Click **New Site**
5. Enter:
   - Host: `ftp.your-hostinger-domain.com`
   - Port: `21` (or `22` for SFTP)
   - Protocol: `FTP` (or `SFTP`)
   - Username: Your FTP username
   - Password: Your FTP password
6. Click **Connect**

**Option B: Using Command Line (SFTP)**

```bash
sftp -P 22 username@ftp.your-hostinger-domain.com
```

## Step 4: Upload Files to Hostinger

### 4.1 Navigate to Public Directory

In FileZilla or SFTP:
1. Navigate to `/public_html/` (or your domain's public folder)
2. This is your web root

### 4.2 Upload Build Files

**Method 1: Using FileZilla**
1. In left panel, navigate to your local `dist/` folder
2. In right panel, you're in `/public_html/`
3. Select all files in `dist/`
4. Drag and drop to right panel
5. Wait for upload to complete

**Method 2: Using Command Line**

```bash
# Navigate to dist folder
cd dist

# Upload all files
sftp -P 22 username@ftp.your-hostinger-domain.com << EOF
cd public_html
put -r *
quit
EOF
```

### 4.3 Verify Upload

Check that these files are in `/public_html/`:
- `index.html`
- `assets/` folder
- `favicon.ico` (if present)

## Step 5: Configure Hostinger for Vue Router

### 5.1 Create .htaccess File

Vue Router uses client-side routing. You need to configure the server to serve `index.html` for all routes.

Create a `.htaccess` file in `/public_html/`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # Don't rewrite if it's a real file or directory
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  
  # Rewrite all requests to index.html
  RewriteRule ^ index.html [QSA,L]
</IfModule>
```

**Upload .htaccess:**
1. Create `.htaccess` file locally
2. Upload to `/public_html/` via FTP

### 5.2 Verify .htaccess is Uploaded

In FileZilla:
- Enable "View" → "Show hidden files"
- You should see `.htaccess` in `/public_html/`

## Step 6: Test Deployment

### 6.1 Visit Your Domain

Open browser and go to:
```
https://your-hostinger-domain.com
```

### 6.2 Check Console for Errors

1. Open Developer Tools (F12)
2. Go to **Console** tab
3. Look for errors
4. Check **Network** tab for failed requests

### 6.3 Common Issues

**Issue: Blank Page**
- Check browser console for errors
- Verify `index.html` was uploaded
- Check that assets are loading (Network tab)

**Issue: 404 on Routes**
- Verify `.htaccess` is in `/public_html/`
- Check that mod_rewrite is enabled on Hostinger
- Try accessing `/index.html` directly

**Issue: API Calls Failing**
- Check CORS configuration on Laravel API
- Verify API URL in `.env.production`
- Check Network tab for CORS errors

**Issue: Assets Not Loading**
- Check file paths in Network tab
- Verify `assets/` folder was uploaded
- Check file permissions (should be 644)

## Step 7: Optimize for Production

### 7.1 Enable Gzip Compression

Add to `.htaccess`:

```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 7.2 Set Cache Headers

Add to `.htaccess`:

```apache
<IfModule mod_expires.c>
  ExpiresActive On
  
  # Cache images for 1 month
  ExpiresByType image/jpeg "access plus 1 month"
  ExpiresByType image/gif "access plus 1 month"
  ExpiresByType image/png "access plus 1 month"
  
  # Cache CSS and JS for 1 week
  ExpiresByType text/css "access plus 1 week"
  ExpiresByType application/javascript "access plus 1 week"
  
  # Don't cache HTML
  ExpiresByType text/html "access plus 0 seconds"
</IfModule>
```

### 7.3 Set Security Headers

Add to `.htaccess`:

```apache
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

## Step 8: Custom Domain Setup

### 8.1 Point Domain to Hostinger

1. Go to your domain registrar
2. Update nameservers to Hostinger's:
   - ns1.hostinger.com
   - ns2.hostinger.com
   - ns3.hostinger.com
   - ns4.hostinger.com

Or update A records to Hostinger's IP address.

### 8.2 Configure in Hostinger

1. Go to Hostinger Control Panel
2. Go to **Domains**
3. Add your domain
4. Point to `/public_html/` folder

### 8.3 Enable SSL Certificate

1. Go to **SSL Certificates**
2. Install free Let's Encrypt certificate
3. Enable auto-renewal

## Step 9: Continuous Deployment (Optional)

### 9.1 Automate Builds

Create a GitHub Actions workflow to auto-deploy on push:

Create `.github/workflows/deploy-hostinger.yml`:

```yaml
name: Deploy to Hostinger

on:
  push:
    branches: [main]
    paths:
      - 'resources/js/**'
      - 'package.json'
      - 'vite.config.js'

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: npm install
      
      - name: Build
        run: npm run build
      
      - name: Deploy to Hostinger
        uses: SamKirkland/FTP-Deploy-Action@v4.3.4
        with:
          server: ${{ secrets.FTP_HOST }}
          username: ${{ secrets.FTP_USER }}
          password: ${{ secrets.FTP_PASSWORD }}
          local-dir: ./dist/
          server-dir: /public_html/
          dangerous-clean-slate: false
```

Then add GitHub Secrets:
- `FTP_HOST` - Your FTP host
- `FTP_USER` - Your FTP username
- `FTP_PASSWORD` - Your FTP password

## Troubleshooting

### Issue: "Cannot GET /"
- `.htaccess` not working
- mod_rewrite not enabled
- Check Hostinger support to enable mod_rewrite

### Issue: CORS Errors
- Update `config/cors.php` on Laravel API
- Push to GitHub to redeploy
- Wait for Railway to rebuild

### Issue: Slow Loading
- Enable gzip compression
- Set cache headers
- Optimize images
- Use CDN (optional)

### Issue: Blank Page After Upload
- Check browser console (F12)
- Verify all files uploaded
- Check file permissions
- Try hard refresh (Ctrl+Shift+R)

## Complete .htaccess Template

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # Don't rewrite real files or directories
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  
  # Rewrite to index.html
  RewriteRule ^ index.html [QSA,L]
</IfModule>

# Gzip Compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache Headers
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpeg "access plus 1 month"
  ExpiresByType image/gif "access plus 1 month"
  ExpiresByType image/png "access plus 1 month"
  ExpiresByType text/css "access plus 1 week"
  ExpiresByType application/javascript "access plus 1 week"
  ExpiresByType text/html "access plus 0 seconds"
</IfModule>

# Security Headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

## Summary

1. ✅ Build Vue app: `npm run build`
2. ✅ Configure API URL in `.env.production`
3. ✅ Update CORS on Laravel API
4. ✅ Connect to Hostinger via FTP
5. ✅ Upload `dist/` files to `/public_html/`
6. ✅ Upload `.htaccess` file
7. ✅ Test deployment
8. ✅ Configure custom domain (optional)
9. ✅ Enable SSL certificate
10. ✅ Set up auto-deployment (optional)

## Next Steps

1. Build the Vue app locally
2. Upload to Hostinger
3. Test all routes and API calls
4. Monitor for errors
5. Set up monitoring/analytics
6. Configure email notifications

---

**Your Vue frontend is now ready to deploy to Hostinger!** 🚀
