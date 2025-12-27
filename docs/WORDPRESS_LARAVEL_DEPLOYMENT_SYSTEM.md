# 🚀 WordPress + Laravel Deployment System

Automated deployment from GitHub to Hostinger using GitHub Actions and rsync.

---

## 📦 Overview

This deployment system handles:
- ✅ WordPress site with custom theme
- ✅ Laravel application (optional - with custom public directory structure)
- ✅ Automated builds (Composer + npm)
- ✅ Safe rsync deployment with `--delete` flag
- ✅ Protection of WordPress core, plugins, uploads, and server files

---

## 📁 Project Structure

```
project-root/
├── .github/workflows/
│   └── production-deploy.yml      # GitHub Actions workflow
├── app/                            # Laravel public directory (custom location)
│   └── build/                     # Vite build output (created during build)
├── laravel/                        # Laravel application (optional)
│   ├── app/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── composer.json
│   ├── package.json
│   └── vite.config.js
├── wp-content/
│   ├── themes/
│   │   └── litqr/                 # Your custom theme
│   ├── plugins/                   # Not managed (protected)
│   └── uploads/                   # Not managed (protected)
├── scripts/
│   ├── build-all.sh               # Build everything
│   ├── build-root.sh              # Build root composer
│   ├── build-laravel.sh           # Build Laravel
│   ├── build-theme.sh             # Build theme
│   └── build-dvc-plugin.sh        # Build custom plugin
├── .gitignore                      # Git exclusions
├── .rsyncignore                    # Rsync exclusions
└── README.md                       # This file
```

---

## 🛠️ Installation

### Step 1: Copy Files to Your Project

```bash
# Create necessary directories
mkdir -p .github/workflows
mkdir -p scripts

# Copy workflow file
cp production-deploy.yml .github/workflows/

# Copy build scripts
cp build-*.sh scripts/
chmod +x scripts/*.sh

# Copy configuration files
cp .rsyncignore .
cp .gitignore .
```

---

## 📄 Required Files

### 1. `.github/workflows/production-deploy.yml`

**GitHub Actions workflow that handles deployment.**

```yaml
name: Production Deploy - Hostinger

on:
  workflow_dispatch:

concurrency:
  group: deployment
  cancel-in-progress: true

env:
  DEPLOY_ROOT: "/home/u629590664/domains/litqr.com/public_html"
  SCRIPTS_PATH: "./scripts"
  SSH_PORT: ${{ secrets.SSH_PORT }}
  SSH_HOST: ${{ secrets.SSH_HOST }}
  SSH_USER: ${{ secrets.SSH_USER }}
  SSH_KEY: ${{ secrets.SSH_KEY }}

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      # ==========================================
      # CHECKOUT & SETUP
      # ==========================================
      - name: Checkout code
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      # ==========================================
      # SSH SETUP
      # ==========================================
      - name: Setup SSH
        run: |
          mkdir -p ~/.ssh
          echo "${{ env.SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ env.SSH_PORT }} ${{ env.SSH_HOST }} >> ~/.ssh/known_hosts 2>/dev/null

      # ==========================================
      # BUILD STEPS
      # ==========================================
      # Uncomment if you have a Laravel application
      # - name: Build Laravel
      #   run: bash ${{ env.SCRIPTS_PATH }}/build-laravel.sh

      # Uncomment when ready to build other components:
      # - name: Build ROOT composer dependencies
      #   run: bash ${{ env.SCRIPTS_PATH }}/build-root.sh

      # - name: Build litqr theme
      #   run: bash ${{ env.SCRIPTS_PATH }}/build-theme.sh

      # ==========================================
      # DEPLOYMENT
      # ==========================================
      # Note: Theme include/exclude patterns are kept inline (not in .rsyncignore)
      # because rsync processes them in order:
      #   1. Exclude all themes
      #   2. Include YOUR custom theme(s)
      #   3. Include contents of your theme(s)
      # This pattern protects third-party themes while deploying your custom theme.
      - name: Deploy via rsync
        run: |
          rsync -avzr \
            --delete \
            --exclude-from='.rsyncignore' \
            --exclude='wp-content/themes/' \
            --include='wp-content/themes/litqr/' \
            --include='wp-content/themes/litqr/**' \
            -e "ssh -i ~/.ssh/deploy_key -p ${{ env.SSH_PORT }} -o StrictHostKeyChecking=no" \
            ./ \
            ${{ env.SSH_USER }}@${{ env.SSH_HOST }}:${{ env.DEPLOY_ROOT }}

      # ==========================================
      # POST-DEPLOYMENT (optional)
      # ==========================================
      # Uncomment if you need to run commands on the server after deployment
      # - name: Clear cache on server
      #   run: |
      #     ssh -i ~/.ssh/deploy_key -p ${{ env.SSH_PORT }} ${{ env.SSH_USER }}@${{ env.SSH_HOST }} << 'EOF'
      #       cd ${{ env.DEPLOY_ROOT }}
      #       # Clear Laravel cache
      #       php laravel/artisan cache:clear
      #       php laravel/artisan config:clear
      #       php laravel/artisan route:clear
      #       php laravel/artisan view:clear
      #       # Clear WordPress cache (if using a cache plugin)
      #       # wp cache flush --path=${{ env.DEPLOY_ROOT }}
      #     EOF

      # ==========================================
      # CLEANUP
      # ==========================================
      - name: Cleanup SSH key
        if: always()
        run: rm -f ~/.ssh/deploy_key
```

**Customization:**
- Update `DEPLOY_ROOT` with your server path
- Uncomment build steps as needed
- Update theme name from `litqr` to your theme name

---

### 2. `.rsyncignore`

**Defines what NOT to deploy to the server.**

```
# =====================================
# RSYNC EXCLUSIONS
# =====================================
# This file defines what NOT to sync during deployment
# Used by: .github/workflows/production-deploy.yml

# =====================================
# DEVELOPMENT & VCS
# =====================================
.git/
.github/
.gitignore
.gitattributes
.idea/
.vscode/
.DS_Store
*.log

# =====================================
# DOCUMENTATION
# =====================================
README.md
INSTALLATION.md
DEPLOYMENT-GUIDE.md
GITIGNORE-GUIDE.md
SCRIPTS-README.md
LARAVEL-STRUCTURE.md
FILES-SUMMARY.md
DOCS/

# =====================================
# WORDPRESS CORE (Protected)
# =====================================
# Root WordPress core files
/index.php
/wp-*.php
/xmlrpc.php
/license.txt
/readme.html
/.htaccess

# WordPress core directories
wp-admin/
wp-includes/

# WordPress config (managed manually on server)
wp-config.php
wp-config-sample.php

# =====================================
# WORDPRESS CONTENT (Protected)
# =====================================
# All plugins (none managed through git)
wp-content/plugins/

# All themes except custom ones
# Note: Custom themes are handled in the workflow using exclude-then-include pattern
# This allows protecting third-party themes while deploying only your custom theme(s)
# See: production-deploy.yml for theme include rules

# User uploads
wp-content/uploads/

# Cache & temporary files
wp-content/cache/
wp-content/upgrade/
wp-content/backups/
wp-content/backup*/
wp-content/advanced-cache.php
wp-content/object-cache.php
wp-content/db.php
wp-content/wp-cache-config.php

# =====================================
# LARAVEL (Protected/Runtime)
# =====================================
# Environment files (managed manually on server)
laravel/.env
laravel/.env.*

# Dependencies (built during deployment)
laravel/node_modules/

# Runtime & cache - exclude contents but allow directories
# Note: We need the directories to exist (with .gitignore files)
# but we don't want to sync the generated files inside them
laravel/storage/logs/*
laravel/storage/framework/cache/data/*
laravel/storage/framework/sessions/*
laravel/storage/framework/views/*
laravel/bootstrap/cache/*

# =====================================
# NODE & BUILD
# =====================================
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# =====================================
# SERVER SPECIFIC FILES
# =====================================
php.ini
ads.txt
robots.txt
sitemap.xml
sitemap_index.xml

# =====================================
# OTHER
# =====================================
*.sql
*.sqlite
*.db
*.bak
*.tmp
*.swp
```

**Note:** Place this file in your **project root** (same level as `.gitignore`).

---

### 3. Build Scripts

All scripts go in the `scripts/` directory.

#### `scripts/build-laravel.sh`

**Builds Laravel application with dependencies and frontend assets.**

```bash
#!/bin/bash
# scripts/build-laravel.sh
# Build Laravel application with dependencies and frontend assets
# Note: This Laravel project has a custom structure where public directory is at ../app/
set -euo pipefail

echo "======================================"
echo "Building LARAVEL project..."
echo "======================================"

# Navigate to Laravel directory
if [ ! -d "laravel" ]; then
  echo "⚠️  WARNING: laravel/ directory not found. Skipping Laravel build."
  exit 0
fi

cd laravel

# ==========================================
# Create Laravel Runtime Directories
# ==========================================
echo "→ Creating Laravel runtime directories..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/public

# Set proper permissions (important for deployment)
chmod -R 775 storage bootstrap/cache

echo "✅ Runtime directories created"

# ==========================================
# Composer Dependencies
# ==========================================
if [ ! -f "composer.json" ]; then
  echo "⚠️  No composer.json found. Skipping composer install."
else
  echo "→ Installing composer dependencies (production mode)..."
  composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

  # Verify vendor directory was created
  if [ ! -d "vendor" ]; then
    echo "❌ ERROR: vendor/ directory not created!"
    exit 1
  fi
  
  echo "✅ Composer dependencies installed"
fi

# ==========================================
# Frontend Assets (Vite/Mix)
# ==========================================
if [ -f "package.json" ]; then
  echo "→ Installing npm dependencies..."
  npm ci --prefer-offline --no-audit
  
  echo "→ Building frontend assets..."
  npm run build
  
  # Verify build output exists
  # This project uses custom publicDirectory: '../app' in vite.config.js
  # So Vite outputs to ../app/build/ instead of public/build/
  if [ -d "../app/build" ]; then
    echo "✅ Frontend assets built successfully (Vite → ../app/build/)"
    echo "   Build output location: ./app/build/"
  elif [ -d "public/build" ]; then
    echo "✅ Frontend assets built successfully (Vite → public/build/)"
  elif [ -d "public/css" ] || [ -d "public/js" ]; then
    echo "✅ Frontend assets built successfully (Mix)"
  else
    echo "⚠️  Warning: No build output detected. Is your build configured correctly?"
    echo "   Expected location: ../app/build/ (based on vite.config.js)"
  fi
else
  echo "⚠️  No package.json found. Skipping frontend build."
fi

cd ..

echo "✅ Laravel build complete."
echo ""
```

**Features:**
- ✅ Checks if `laravel/` exists (skips gracefully if not)
- ✅ Creates Laravel runtime directories
- ✅ Installs Composer dependencies (production mode)
- ✅ Builds frontend assets with Vite
- ✅ Handles custom `../app/build/` output directory

---

#### `scripts/build-root.sh`

**Builds root-level Composer dependencies.**

```bash
#!/bin/bash
# scripts/build-root.sh
# Build ROOT composer dependencies for WordPress project
set -euo pipefail

echo "======================================"
echo "Building ROOT composer project..."
echo "======================================"

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
  echo "⚠️  No composer.json found in root. Skipping root build."
  exit 0
fi

# Install composer dependencies
echo "→ Installing composer dependencies (production mode)..."
composer install \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader \
  --no-interaction \
  --no-progress

# Verify vendor directory was created
if [ ! -d "vendor" ]; then
  echo "❌ ERROR: vendor/ directory not created!"
  exit 1
fi

echo "✅ ROOT build complete."
echo ""
```

---

#### `scripts/build-theme.sh`

**Builds WordPress theme with dependencies and assets.**

```bash
#!/bin/bash
# scripts/build-theme.sh
# Build amazingfactshome.com WordPress theme
set -euo pipefail

THEME_NAME="litqr"
THEME_PATH="wp-content/themes/${THEME_NAME}"

echo "======================================"
echo "Building ${THEME_NAME} theme..."
echo "======================================"

# Check if theme directory exists
if [ ! -d "${THEME_PATH}" ]; then
  echo "❌ ERROR: ${THEME_PATH} directory not found!"
  echo "   Make sure the theme is tracked in git and .gitignore is configured correctly."
  exit 1
fi

cd "${THEME_PATH}"

# ==========================================
# Composer Dependencies
# ==========================================
if [ -f "composer.json" ]; then
  echo "→ Installing composer dependencies (production mode)..."
  composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress
  
  # Verify vendor directory was created
  if [ ! -d "vendor" ]; then
    echo "❌ ERROR: vendor/ directory not created!"
    exit 1
  fi
  
  echo "✅ Composer dependencies installed"
else
  echo "⚠️  No composer.json found. Skipping composer install."
fi

# ==========================================
# Frontend Assets
# ==========================================
if [ -f "package.json" ]; then
  echo "→ Installing npm dependencies..."
  npm ci --prefer-offline --no-audit
  
  echo "→ Building theme assets (JS & SCSS)..."
  npm run build
  
  # Verify build output exists (common output directories)
  if [ -d "dist" ] || [ -d "build" ] || [ -d "assets/dist" ]; then
    echo "✅ Theme assets built successfully"
  else
    echo "⚠️  Warning: No dist/build directory detected. Check your build configuration."
  fi
else
  echo "⚠️  No package.json found. Skipping npm build."
fi

cd - > /dev/null

echo "✅ ${THEME_NAME} theme build complete."
echo ""
```

**Customization:** Update `THEME_NAME` variable to match your theme.

---

#### `scripts/build-all.sh`

**Builds all project components (useful for local testing).**

```bash
#!/bin/bash
# scripts/build-all.sh
# Build all project components (useful for local testing before deployment)
set -euo pipefail

SCRIPTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=========================================="
echo "🚀 Building ALL project components..."
echo "=========================================="
echo ""

# Track build status
BUILD_ERRORS=0

# ==========================================
# Build Root
# ==========================================
if [ -f "${SCRIPTS_DIR}/build-root.sh" ]; then
  if bash "${SCRIPTS_DIR}/build-root.sh"; then
    echo ""
  else
    echo "❌ Root build failed!"
    BUILD_ERRORS=$((BUILD_ERRORS + 1))
  fi
fi

# ==========================================
# Build Laravel
# ==========================================
if [ -f "${SCRIPTS_DIR}/build-laravel.sh" ]; then
  if bash "${SCRIPTS_DIR}/build-laravel.sh"; then
    echo ""
  else
    echo "❌ Laravel build failed!"
    BUILD_ERRORS=$((BUILD_ERRORS + 1))
  fi
fi

# ==========================================
# Build Theme
# ==========================================
if [ -f "${SCRIPTS_DIR}/build-theme.sh" ]; then
  if bash "${SCRIPTS_DIR}/build-theme.sh"; then
    echo ""
  else
    echo "❌ Theme build failed!"
    BUILD_ERRORS=$((BUILD_ERRORS + 1))
  fi
fi

# ==========================================
# Summary
# ==========================================
echo "=========================================="
if [ $BUILD_ERRORS -eq 0 ]; then
  echo "✅ All builds completed successfully!"
  echo "=========================================="
  exit 0
else
  echo "❌ Build completed with ${BUILD_ERRORS} error(s)"
  echo "=========================================="
  exit 1
fi
```

---

## ⚙️ Configuration

### GitHub Secrets

Add these secrets in GitHub: **Settings** → **Secrets and variables** → **Actions**

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `SSH_HOST` | SSH hostname | `ssh.hostinger.com` |
| `SSH_PORT` | SSH port | `65002` or `22` |
| `SSH_USER` | SSH username | `u629590664` |
| `SSH_KEY` | SSH private key | Full contents of private key file |

**How to get SSH credentials:** See the main README or INSTALLATION.md for detailed instructions on setting up SSH keys with Hostinger.

---

### Workflow Customization

Edit `.github/workflows/production-deploy.yml`:

1. **Update deployment path:**
   ```yaml
   DEPLOY_ROOT: "/home/u629590664/domains/yourdomain.com/public_html"
   ```

2. **Enable build steps:** Uncomment the builds you need:
   ```yaml
   - name: Build Laravel
     run: bash ${{ env.SCRIPTS_PATH }}/build-laravel.sh
   ```

3. **Update theme name:**
   ```yaml
   --include='wp-content/themes/your-theme-name/'
   --include='wp-content/themes/your-theme-name/**'
   ```

---

## 🚀 Usage

### Local Testing

Test builds locally before deploying:

```bash
# Make scripts executable
chmod +x scripts/*.sh

# Test all builds
./scripts/build-all.sh

# Or test individual builds
./scripts/build-laravel.sh
./scripts/build-theme.sh
```

### Deploy to Production

1. **Commit and push your changes:**
   ```bash
   git add .
   git commit -m "Your commit message"
   git push
   ```

2. **Trigger deployment:**
   - Go to GitHub → **Actions** tab
   - Select **"Production Deploy - Hostinger"**
   - Click **"Run workflow"**
   - Select branch (usually `main`)
   - Click **"Run workflow"**

3. **Monitor deployment:**
   - Watch the workflow logs in real-time
   - Check for any errors or warnings
   - Verify your website after deployment

---

## 🛡️ What's Protected (Never Touched by Deployment)

The deployment system protects:

- ✅ WordPress core files (`wp-admin/`, `wp-includes/`, root `.php` files)
- ✅ WordPress configuration (`wp-config.php`)
- ✅ All WordPress plugins (`wp-content/plugins/`)
- ✅ Third-party themes (only YOUR theme deploys)
- ✅ User uploads (`wp-content/uploads/`)
- ✅ Cache and logs
- ✅ Environment files (`.env` files)
- ✅ Server-specific files (`php.ini`, `ads.txt`, `robots.txt`)

---

## 📦 What Gets Deployed

- ✅ Your custom theme (`wp-content/themes/litqr/`)
- ✅ Laravel application (`laravel/` + `app/`)
- ✅ Build artifacts (`app/build/`, theme assets)
- ✅ Root files you've committed
- ✅ Build scripts and configuration

---

## 🔄 How --delete Works

The `--delete` flag removes files from the server that were deleted from git, **but only in managed areas**:

### Will Be Deleted (if removed from git):
- Files in your custom theme
- Files in Laravel application
- Root files you committed

### Will NOT Be Deleted (protected):
- WordPress core files
- Third-party plugins
- Uploads
- Any file/folder in `.rsyncignore`

---

## 🐛 Troubleshooting

### Build Fails: "laravel/ directory not found"

**Cause:** Laravel directory doesn't exist or isn't in git

**Solution:**
- If you have Laravel: `git add laravel/ && git commit && git push`
- If you don't: The script will skip gracefully (no error)

### Deployment Deletes Files

**Cause:** Files aren't excluded properly

**Solution:**
1. Check `.rsyncignore` - add patterns for files to protect
2. Verify files are excluded from git (`.gitignore`)
3. Test with `--dry-run` first (add to workflow temporarily)

### SSH Connection Failed

**Cause:** SSH credentials incorrect

**Solution:**
1. Verify all 4 GitHub secrets are set correctly
2. Test SSH connection manually from your computer
3. Check Hostinger SSH access is enabled

### Theme/Plugin Not Deploying

**Cause:** Not whitelisted in workflow or not in git

**Solution:**
1. Check `.gitignore` - theme should be whitelisted
2. Check workflow - theme should be in `--include` rules
3. Verify: `git ls-tree -r HEAD | grep "your-theme"`

---

## 📚 Additional Documentation

- **`INSTALLATION.md`** - Detailed setup instructions
- **`DEPLOYMENT-GUIDE.md`** - Deep dive into rsync behavior
- **`LARAVEL-STRUCTURE.md`** - Custom Laravel directory structure
- **`RSYNC-REFACTOR-GUIDE.md`** - Why we use `.rsyncignore`

---

## 🎯 Best Practices

1. ✅ **Always test builds locally** before deploying
2. ✅ **Use meaningful commit messages**
3. ✅ **Monitor deployment logs** for errors
4. ✅ **Backup database** before first deployment
5. ✅ **Test website** immediately after deployment
6. ✅ **Keep secrets secure** - never commit sensitive data
7. ✅ **Use `--dry-run`** for first deployment to verify behavior

---

## 🔐 Security Notes

- ✅ Never commit `.env` files or secrets
- ✅ Never commit `wp-config.php`
- ✅ Use GitHub Secrets for all credentials
- ✅ Rotate SSH keys periodically
- ✅ Monitor deployment logs for suspicious activity
- ✅ Keep WordPress and plugins updated on server

---

## 🆘 Getting Help

If you encounter issues:

1. Check the **Troubleshooting** section above
2. Review **GitHub Actions logs** for error messages
3. Test **SSH connection** manually
4. Verify **all files are in correct locations**
5. Check **`.rsyncignore`** patterns

---

## 📝 Quick Reference

### File Locations

```
.rsyncignore              → Project root
.gitignore                → Project root
production-deploy.yml     → .github/workflows/
build-*.sh               → scripts/
```

### Common Commands

```bash
# Test builds locally
./scripts/build-all.sh

# Deploy
# GitHub → Actions → Run workflow

# Check what's in git
git ls-tree -r HEAD --name-only

# Test SSH
ssh -i ~/.ssh/deploy_key -p PORT USER@HOST
```

---

**Happy Deploying!** 🚀

If this README helped you, consider starring the repository!
