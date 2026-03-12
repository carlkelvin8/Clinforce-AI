@echo off
REM Hostinger Vue Deployment Script for Windows
REM This script builds and prepares your Vue app for Hostinger deployment

setlocal enabledelayedexpansion

echo.
echo ==========================================
echo Clinforce AI - Hostinger Deployment
echo ==========================================
echo.

REM Step 1: Install dependencies
echo [Step 1] Installing dependencies...
call npm install
if errorlevel 1 (
    echo Error: npm install failed
    exit /b 1
)
echo [OK] Dependencies installed
echo.

REM Step 2: Build for production
echo [Step 2] Building for production...
call npm run build
if errorlevel 1 (
    echo Error: npm run build failed
    exit /b 1
)
echo [OK] Build complete
echo.

REM Step 3: Verify build
echo [Step 3] Verifying build...
if exist "dist" (
    echo [OK] dist/ folder created
    echo Files in dist/:
    dir dist /b
) else (
    echo [ERROR] dist/ folder not found
    exit /b 1
)
echo.

REM Step 4: Check .htaccess
echo [Step 4] Checking .htaccess...
if exist ".htaccess" (
    echo [OK] .htaccess file found
) else (
    echo [INFO] Creating .htaccess...
    (
        echo ^<IfModule mod_rewrite.c^>
        echo   RewriteEngine On
        echo   RewriteBase /
        echo   RewriteCond %%{REQUEST_FILENAME} !-f
        echo   RewriteCond %%{REQUEST_FILENAME} !-d
        echo   RewriteRule ^ index.html [QSA,L]
        echo ^</IfModule^>
    ) > .htaccess
    echo [OK] .htaccess created
)
echo.

REM Step 5: Summary
echo ==========================================
echo Deployment Ready!
echo ==========================================
echo.
echo Next steps:
echo 1. Connect to Hostinger via FTP
echo 2. Upload contents of 'dist\' to /public_html/
echo 3. Upload '.htaccess' to /public_html/
echo 4. Visit your domain to verify
echo.
echo Files ready for upload:
echo   - dist\ (all files)
echo   - .htaccess
echo.
echo IMPORTANT:
echo - Update VITE_API_URL in .env.production.local
echo - Ensure CORS is enabled on your Laravel API
echo - Enable mod_rewrite on Hostinger
echo.
echo Build complete! Ready to deploy.
echo.
pause
