#!/bin/bash

# Hostinger Vue Deployment Script
# This script builds and prepares your Vue app for Hostinger deployment

set -e

echo "=========================================="
echo "Clinforce AI - Hostinger Deployment"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Install dependencies
echo -e "${BLUE}Step 1: Installing dependencies...${NC}"
npm install
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# Step 2: Build for production
echo -e "${BLUE}Step 2: Building for production...${NC}"
npm run build
echo -e "${GREEN}✓ Build complete${NC}"
echo ""

# Step 3: Verify build
echo -e "${BLUE}Step 3: Verifying build...${NC}"
if [ -d "dist" ]; then
    echo -e "${GREEN}✓ dist/ folder created${NC}"
    echo "  Files in dist/:"
    ls -lh dist/ | tail -n +2 | awk '{print "    " $9 " (" $5 ")"}'
else
    echo -e "${YELLOW}✗ dist/ folder not found${NC}"
    exit 1
fi
echo ""

# Step 4: Check .htaccess
echo -e "${BLUE}Step 4: Checking .htaccess...${NC}"
if [ -f ".htaccess" ]; then
    echo -e "${GREEN}✓ .htaccess file found${NC}"
else
    echo -e "${YELLOW}✗ .htaccess file not found${NC}"
    echo "  Creating .htaccess..."
    cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ index.html [QSA,L]
</IfModule>
EOF
    echo -e "${GREEN}✓ .htaccess created${NC}"
fi
echo ""

# Step 5: Summary
echo -e "${BLUE}=========================================="
echo "Deployment Ready!"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Connect to Hostinger via FTP"
echo "2. Upload contents of 'dist/' to /public_html/"
echo "3. Upload '.htaccess' to /public_html/"
echo "4. Visit your domain to verify"
echo ""
echo "Files ready for upload:"
echo "  - dist/ (all files)"
echo "  - .htaccess"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "- Update VITE_API_URL in .env.production.local"
echo "- Ensure CORS is enabled on your Laravel API"
echo "- Enable mod_rewrite on Hostinger"
echo ""
echo -e "${GREEN}Build complete! Ready to deploy.${NC}"
