# Paano Ayusin ang Vite CORS Error sa Production

## Problema
Nakikita mo ang error na ito:
```
Access to script at 'http://[::1]:5173/@vite/client' from origin 'https://app.aiclinforce.com' 
has been blocked by CORS policy
```

## Bakit Nangyayari Ito?
Ang production site mo ay nag-load pa rin ng Vite development server (port 5173) imbes na ang built assets. Kailangan i-configure ang `.env` file sa Hostinger server.

## Solusyon: Sundin ang mga hakbang na ito

### Opsyon 1: Gamitin ang Automated Script (Pinakamadali)

1. **SSH sa Hostinger server mo:**
   ```bash
   ssh your_username@your_server
   cd /path/to/your/app
   ```

2. **I-pull ang latest code:**
   ```bash
   git pull origin feature/apr25-updates
   ```

3. **Patakbuhin ang fix script:**
   ```bash
   chmod +x fix-production-vite.sh
   ./fix-production-vite.sh
   ```

4. **Tapos na! I-refresh ang browser.**

---

### Opsyon 2: Manual na Pag-ayos

Kung hindi gumagana ang script, gawin mo ito manually:

#### Hakbang 1: I-edit ang .env file sa Hostinger

```bash
ssh your_username@your_server
cd /path/to/your/app
nano .env
```

#### Hakbang 2: Baguhin ang mga settings na ito

**IMPORTANTE: Siguraduhing ganito ang settings:**

```env
# Baguhin ito:
APP_ENV=production          # HINDI local
APP_DEBUG=false            # HINDI true

# Tanggalin o i-comment out ang line na ito kung meron:
# VITE_DEV_SERVER_URL=http://localhost:5173

# I-update ang API URL:
VITE_API_URL=https://app.aiclinforce.com/api

# I-update ang database credentials (kung hindi pa):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_database
DB_USERNAME=your_hostinger_user
DB_PASSWORD=your_hostinger_password
```

**I-save ang file:**
- Press `Ctrl + X`
- Press `Y`
- Press `Enter`

#### Hakbang 3: I-pull ang latest code

```bash
git pull origin feature/apr25-updates
```

#### Hakbang 4: I-build ang frontend assets

```bash
npm install
npm run build
```

Dapat makita mo ang:
```
✓ built in 45.23s
✓ 1234 modules transformed
```

#### Hakbang 5: I-check kung may public/build folder

```bash
ls -la public/build/
```

Dapat may makita kang:
- `manifest.json`
- `assets/` folder na may `.js` at `.css` files

#### Hakbang 6: I-clear ang Laravel caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Hakbang 7: I-restart ang web server (kung kailangan)

Para sa Apache:
```bash
sudo systemctl restart apache2
```

Para sa Nginx:
```bash
sudo systemctl restart nginx
```

#### Hakbang 8: I-test sa browser

1. Buksan ang https://app.aiclinforce.com
2. Press `F12` para buksan ang DevTools
3. Pumunta sa **Network** tab
4. I-refresh ang page (`Ctrl + Shift + R` para hard refresh)
5. Tingnan kung ang assets ay galing sa `/build/assets/` HINDI sa `[::1]:5173`

**Tama na URLs (dapat ganito):**
```
✅ https://app.aiclinforce.com/build/assets/app-abc123.js
✅ https://app.aiclinforce.com/build/assets/app-xyz789.css
```

**Mali na URLs (ang problema):**
```
❌ http://[::1]:5173/@vite/client
❌ http://[::1]:5173/resources/js/app.js
```

---

## Troubleshooting: Kung hindi pa rin gumagana

### Check 1: Verify .env settings
```bash
cat .env | grep -E "APP_ENV|APP_DEBUG|VITE"
```

Dapat makita mo:
```
APP_ENV=production
APP_DEBUG=false
VITE_API_URL=https://app.aiclinforce.com/api
```

**HINDI dapat makita:**
```
VITE_DEV_SERVER_URL=...
```

### Check 2: Verify na may built assets
```bash
ls -la public/build/manifest.json
```

Kung walang file, i-run ulit:
```bash
npm run build
```

### Check 3: Clear browser cache
Sa browser:
1. Press `Ctrl + Shift + Delete`
2. I-select ang "Cached images and files"
3. I-click ang "Clear data"
4. I-refresh ang page

### Check 4: Check web server logs
```bash
# Para sa Apache
tail -f /var/log/apache2/error.log

# Para sa Nginx
tail -f /var/log/nginx/error.log
```

### Check 5: Verify file permissions
```bash
chmod -R 755 storage bootstrap/cache public/build
chown -R www-data:www-data storage bootstrap/cache
```

(Palitan ang `www-data` kung iba ang web server user mo)

---

## Checklist: Bago mag-deploy

- [ ] ✅ `APP_ENV=production` sa .env
- [ ] ✅ `APP_DEBUG=false` sa .env
- [ ] ✅ Walang `VITE_DEV_SERVER_URL` sa .env
- [ ] ✅ `VITE_API_URL=https://app.aiclinforce.com/api`
- [ ] ✅ Na-pull na ang latest code
- [ ] ✅ Na-run na ang `npm run build`
- [ ] ✅ May `public/build/manifest.json` file
- [ ] ✅ Na-clear na ang lahat ng Laravel caches
- [ ] ✅ Na-restart na ang web server
- [ ] ✅ Na-test na sa browser (check Network tab)

---

## Importante: Development vs Production

### Local Development (sa computer mo)
```env
APP_ENV=local
APP_DEBUG=true
VITE_API_URL=http://localhost:8000/api
```
I-run: `npm run dev` (nag-start ng Vite dev server)

### Production (sa Hostinger)
```env
APP_ENV=production
APP_DEBUG=false
VITE_API_URL=https://app.aiclinforce.com/api
# Walang VITE_DEV_SERVER_URL
```
I-run: `npm run build` (gumawa ng static files sa public/build/)

---

## Kung kailangan mo ng tulong

1. I-check ang Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. I-check kung gumagana ang API:
   ```bash
   curl https://app.aiclinforce.com/api/health
   ```

3. I-verify ang lahat ng config:
   ```bash
   php artisan config:show
   ```

4. Kung hindi pa rin gumagana, i-send sa akin ang output ng:
   ```bash
   cat .env | grep -E "APP_ENV|APP_DEBUG|VITE"
   ls -la public/build/
   php artisan --version
   ```

---

## Summary (TL;DR)

**Ang kailangan mo lang gawin:**

1. SSH sa Hostinger
2. `cd /path/to/your/app`
3. `git pull origin feature/apr25-updates`
4. `chmod +x fix-production-vite.sh && ./fix-production-vite.sh`
5. I-refresh ang browser

**O kaya manual:**
1. I-edit ang `.env`: Set `APP_ENV=production`, `APP_DEBUG=false`, tanggalin ang `VITE_DEV_SERVER_URL`
2. `npm run build`
3. `php artisan config:clear && php artisan config:cache`
4. I-refresh ang browser

Tapos na! 🎉
