# Hostinger Vue Deployment - Quick Start

## ✅ Build Complete!

Your Vue application has been built successfully.

**Build Size**: 3.52 MB
**Location**: `public/build/` folder

## Files Ready to Upload

```
public/build/
├── index.html (main entry point)
├── manifest.json
└── assets/
    ├── *.js (JavaScript files)
    ├── *.css (Stylesheets)
    ├── *.woff2 (Fonts)
    └── *.png (Images)
```

## Step 1: Update API URL

Edit `.env.production.local`:

```env
VITE_API_URL=https://your-railway-api-domain.com/api
```

Replace `your-railway-api-domain.com` with your actual Railway domain.

## Step 2: Connect to Hostinger via FTP

### Using FileZilla (Recommended)

1. Download: https://filezilla-project.org/
2. File → Site Manager → New Site
3. Enter:
   - **Host**: `ftp.your-hostinger-domain.com`
   - **Port**: `21` (or `22` for SFTP)
   - **Protocol**: `FTP` (or `SFTP`)
   - **Username**: Your FTP username
   - **Password**: Your FTP password
4. Click **Connect**

### Using Command Line (SFTP)

```powershell
sftp -P 22 username@ftp.your-hostinger-domain.com
```

## Step 3: Upload Files

### Method 1: FileZilla (Drag & Drop)

1. Left panel: Navigate to `public/build/` folder
2. Right panel: Navigate to `/public_html/`
3. Select all files in `public/build/`
4. Drag to right panel
5. Wait for upload to complete

### Method 2: Command Line

```powershell
# Navigate to build folder
cd public/build

# Upload all files
sftp -P 22 username@ftp.your-hostinger-domain.com << EOF
cd public_html
put -r *
quit
EOF
```

## Step 4: Upload .htaccess

1. In FileZilla, enable "View" → "Show hidden files"
2. Upload `.htaccess` file to `/public_html/`

This file enables Vue Router to work properly.

## Step 5: Verify Upload

Check that these files are in `/public_html/`:
- ✅ `index.html`
- ✅ `assets/` folder
- ✅ `.htaccess`
- ✅ `manifest.json`

## Step 6: Test Your Site

1. Open browser
2. Go to: `https://your-hostinger-domain.com`
3. You should see your Vue app loading

### Troubleshooting

**Blank Page?**
- Press Ctrl+Shift+R (hard refresh)
- Check browser console (F12) for errors
- Verify all files uploaded

**404 on Routes?**
- Verify `.htaccess` is in `/public_html/`
- Check that mod_rewrite is enabled
- Contact Hostinger support if needed

**API Calls Failing?**
- Check CORS is enabled on Laravel API
- Verify API URL in `.env.production.local`
- Check Network tab in browser (F12)

## Step 7: Configure Custom Domain (Optional)

1. Go to Hostinger Control Panel
2. Go to **Domains**
3. Add your domain
4. Point to `/public_html/` folder
5. Enable SSL certificate

## Step 8: Enable SSL Certificate

1. Go to Hostinger Control Panel
2. Go to **SSL Certificates**
3. Install free Let's Encrypt certificate
4. Enable auto-renewal

## Complete Checklist

- [ ] Build completed (`npm run build`)
- [ ] API URL updated in `.env.production.local`
- [ ] Connected to Hostinger via FTP
- [ ] Uploaded `public/build/*` to `/public_html/`
- [ ] Uploaded `.htaccess` to `/public_html/`
- [ ] Verified files are in `/public_html/`
- [ ] Tested site loads at your domain
- [ ] Tested routes work (Vue Router)
- [ ] Tested API calls work
- [ ] SSL certificate enabled
- [ ] Custom domain configured (if applicable)

## Performance Tips

1. **Enable Gzip** - Already in `.htaccess`
2. **Set Cache Headers** - Already in `.htaccess`
3. **Optimize Images** - Consider using WebP format
4. **Monitor Performance** - Use Lighthouse in Chrome DevTools

## Support

- **Hostinger Help**: https://support.hostinger.com
- **Vue Documentation**: https://vuejs.org
- **Vite Documentation**: https://vitejs.dev

## Next Steps

1. ✅ Build Vue app (DONE)
2. → Upload to Hostinger
3. → Test all features
4. → Monitor for errors
5. → Set up analytics
6. → Configure monitoring

---

**Your Vue app is ready to deploy!** 🚀

For detailed instructions, see `HOSTINGER_VUE_DEPLOYMENT.md`
