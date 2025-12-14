# Laravel Starter Kit

A modern Laravel 12 starter kit with authentication, organized asset structure, and production-ready security middleware.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## Features

- ✅ **Laravel 12** with latest features
- ✅ **Laravel Breeze** authentication system
- ✅ **Organized asset structure** (pages, components, layouts)
- ✅ **Security middleware** (BasicAuth, Sandbox, API Auth, CSRF)
- ✅ **Vite with glob patterns** for flexible asset compilation
- ✅ **Database table prefixing** support
- ✅ **Request logging** with unique UUIDs
- ✅ **Tailwind CSS 3.x** + Alpine.js
- ✅ **Development-friendly** scripts and commands

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Middleware](#middleware)
- [Asset Structure](#asset-structure)
- [Database](#database)
- [Security](#security)
- [Development](#development)
- [Deployment](#deployment)
- [WordPress Integration](#wordpress-integration-optional)
- [Contributing](#contributing)
- [License](#license)

## Prerequisites

- **PHP** 8.2 or higher
- **Composer** 2.x
- **Node.js** 18+ and npm
- **MySQL** 8.0+ / **MariaDB** 10.3+ / **PostgreSQL** 13+
- **Web Server** (Apache/Nginx) or PHP built-in server

## Installation

### Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/laravel-starter-kit.git
cd laravel-starter-kit

# 2. Install dependencies and setup
composer setup
```

The `composer setup` command automatically:
- Installs PHP dependencies
- Copies `.env.example` to `.env`
- Generates application key
- Runs database migrations
- Installs npm packages
- Builds frontend assets

### Manual Setup

If you prefer manual installation:

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# Then run migrations
php artisan migrate

# Install frontend dependencies
npm install

# Build assets
npm run build
```

## Configuration

### Environment Setup

Edit your `.env` file:

```env
# Application
APP_NAME="Your App Name"
APP_ENV=local
APP_URL=https://your-app.test

# Database
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_PREFIX=laravel_

# Basic Authentication (for sandbox routes)
BASIC_AUTH_USER=admin
BASIC_AUTH_PASS=secure-password

# Sandbox Access (comma-separated emails)
SANDBOX_ALLOWED_EMAILS=admin@example.com,dev@example.com
```

### API Authentication (Optional)

If you plan to use API routes with key-based authentication:

```bash
# Generate secure API keys
php artisan make:api-key

# Or generate multiple keys at once
php artisan make:api-key --show-all
```

Add to `.env`:
```env
API_KEY_1=generated-key-here
API_KEY_2=another-key-here
```

## Middleware

This starter kit includes several custom middleware for common use cases.

### Available Middleware

#### 1. **BasicAuth** - HTTP Basic Authentication
Protects routes with username/password authentication.

```php
Route::middleware(['basic.auth'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
```

**Features:**
- Automatically disabled in `local` environment
- Fails closed if credentials not configured
- Perfect for staging/demo environments

**Configuration:**
```env
BASIC_AUTH_USER=admin
BASIC_AUTH_PASS=secure-password
```

#### 2. **Sandbox** - Email Allowlist
Restricts access to routes based on authenticated user's email.

```php
Route::middleware(['auth', 'sandbox'])->group(function () {
    Route::get('/sandbox', [SandboxController::class, 'index']);
});
```

**Features:**
- Requires authentication
- Email must be in allowlist
- Automatically disabled in `local` environment

**Configuration:**
```env
SANDBOX_ALLOWED_EMAILS=admin@example.com,dev@example.com
```

#### 3. **ApiAuth** - API Key Authentication
Validates API requests using `X-API-KEY` header.

```php
Route::middleware(['api.auth'])->group(function () {
    Route::get('/api/users', [ApiController::class, 'index']);
});
```

**Usage:**
```bash
curl -H "X-API-KEY: your-key-here" https://your-app.com/api/users
```

**Generate keys:**
```bash
php artisan make:api-key
```

#### 4. **Log** - Request Tracking
Attaches unique UUID to each request for debugging.

```php
// Access in controller
$uuid = $request->attributes->get('log_uuid');

// Available in response header
X-Log-UUID: 550e8400-e29b-41d4-a716-446655440000
```

#### 5. **Signed** - Validate Signed URLs
For secure temporary URLs (unsubscribe links, downloads, etc.)

```php
// Generate signed URL
$url = URL::signedRoute('unsubscribe', ['user' => 1], now()->addDays(30));

// Protect route
Route::get('/unsubscribe/{user}', ...)
    ->middleware(['signed'])
    ->name('unsubscribe');
```

#### 6. **CSRF Protection**
Automatic CSRF protection with webhook exemptions.

```php
// Already configured to exclude webhooks
protected $except = [
    'webhooks/*',
];
```

**Full middleware documentation:** [MIDDLEWARE.md](MIDDLEWARE.md)

## Asset Structure

Assets are organized by purpose for better maintainability:

```
resources/assets/
├── css/
│   └── app.css          # Main stylesheet
├── js/
│   ├── app.js           # Main JavaScript
│   └── bootstrap.js     # Axios & dependencies
├── pages/               # Page-specific assets
│   └── home/
│       ├── home.js
│       └── home.scss
├── components/          # Component-specific assets
│   └── navbar/
│       ├── navbar.js
│       └── navbar.scss
└── layouts/             # Layout-specific assets
    └── app/
        ├── app.js
        └── app.scss
```

**Vite automatically compiles** all `.css`, `.scss`, and `.js` files in these directories.

### Using Assets in Blade

```blade
@vite([
    'resources/assets/css/app.css',
    'resources/assets/js/app.js',
    'resources/assets/pages/home/home.js',
])
```

## Database

### Table Prefixing

All tables are prefixed with `laravel_` by default to prevent conflicts.

```env
DB_PREFIX=laravel_
```

**Resulting tables:**
- `laravel_users`
- `laravel_sessions`
- `laravel_cache`
- etc.

To change the prefix, update `.env`:
```env
DB_PREFIX=myapp_
```

### Migrations

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh

# Seed database
php artisan db:seed
```

## Security

This starter kit includes multiple security layers:

### Authentication
- ✅ Secure password hashing (bcrypt)
- ✅ Rate limiting on login (5 attempts/minute)
- ✅ Email verification support
- ✅ Remember me functionality

### Request Security
- ✅ CSRF protection (with webhook exemptions)
- ✅ HTTP Basic Auth for internal routes
- ✅ API key authentication
- ✅ Signed URL validation
- ✅ Request logging with UUIDs

### Best Practices
- ✅ Input trimming (except passwords)
- ✅ Database table prefixing
- ✅ Environment-based middleware
- ✅ Fail-closed security

**Full security documentation:** [SECURITY.md](SECURITY.md)

## Development

### Available Commands

```bash
# Run all development services (recommended)
composer dev
# This runs:
# - Laravel dev server (port 8000)
# - Queue worker
# - Log viewer (Pail)
# - Vite dev server (HMR)

# Individual commands
php artisan serve          # Development server
php artisan queue:work     # Queue worker
php artisan pail           # Log viewer
npm run dev                # Vite dev server

# Testing
composer test              # Run tests
php artisan test           # Run specific tests

# Code quality
./vendor/bin/php-cs-fixer fix   # Format code
```

### Development URLs

After running `composer dev`:
- **Application:** http://localhost:8000
- **Vite HMR:** http://localhost:5173

### Sandbox Routes

Protected development routes at `/sandbox`:

```
/sandbox           # Status endpoint
/sandbox/ping      # Simple ping test
```

**Access requires:**
1. Authenticated user (logged in)
2. Email in allowlist (production)
3. Basic auth credentials (if configured)

## Deployment

### Production Checklist

```bash
# 1. Set environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# 2. Build assets
npm run build

# 3. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Set permissions
chmod -R 755 storage bootstrap/cache

# 5. Generate strong keys
php artisan make:api-key --show-all
```

### Environment Variables

**Required in production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:...

# Strong credentials
BASIC_AUTH_PASS=strong-password-here

# Configure mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
```

**Security settings:**
```env
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

## WordPress Integration (Optional)

This starter kit can be integrated with WordPress using the [WP Laravel Loader Plugin](https://github.com/ahmu83/wp-laravel-loader).

### Quick Setup

1. **Install Laravel in WordPress directory:**
   ```bash
   # Install in: /path/to/wordpress/laravel/
   # Public path: /path/to/wordpress/app/
   ```

2. **Update Vite configuration:**
   ```javascript
   // vite.config.js
   publicDirectory: '../app',  // Change from './public'
   ```

3. **Update environment:**
   ```env
   ASSET_URL=https://yourdomain.com/app
   ```

4. **Install WP Laravel Loader plugin** in WordPress

**Full integration guide:** [WP Laravel Loader Documentation](https://github.com/ahmu83/wp-laravel-loader)

## Project Structure

```
laravel-starter-kit/
├── app/
│   ├── Console/Commands/
│   │   └── GenerateApiKey.php    # API key generator
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   │   ├── ApiAuth.php        # API authentication
│   │   │   ├── BasicAuth.php      # HTTP basic auth
│   │   │   ├── Log.php            # Request logging
│   │   │   ├── SandboxMiddleware.php
│   │   │   ├── TrimStrings.php
│   │   │   ├── ValidateSignature.php
│   │   │   └── VerifyCsrfToken.php
│   │   └── Requests/
├── bootstrap/
│   ├── app.php                    # Middleware registration
│   └── routes.php                 # Route configuration
├── config/
│   ├── api_auth.php               # API key configuration
│   ├── basic_auth.php             # Basic auth config
│   └── sandbox.php                # Sandbox allowlist
├── resources/
│   ├── assets/                    # Organized asset structure
│   │   ├── css/
│   │   ├── js/
│   │   ├── pages/
│   │   ├── components/
│   │   └── layouts/
│   └── views/
├── routes/
│   ├── web.php                    # Main routes
│   ├── api.php                    # API routes
│   ├── auth.php                   # Authentication routes
│   ├── sandbox.php                # Sandbox routes
│   └── web-redirects.php          # URL redirects
├── .env.example                   # Environment template
├── MIDDLEWARE.md                  # Middleware docs
├── SECURITY.md                    # Security guide
└── README.md                      # This file
```

## Useful Commands

```bash
# Generate API key
php artisan make:api-key

# Generate multiple keys
php artisan make:api-key --show-all

# Copy key to clipboard
php artisan make:api-key --copy

# Clear all caches
php artisan optimize:clear

# List all routes
php artisan route:list

# Create new middleware
php artisan make:middleware YourMiddleware

# Create new controller
php artisan make:controller YourController

# Run specific test
php artisan test --filter=YourTest
```

## Troubleshooting

### Common Issues

**Assets not loading:**
```bash
npm run build
php artisan config:clear
```

**Database errors:**
```bash
php artisan migrate:fresh
php artisan db:seed
```

**Permission issues:**
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**API authentication not working:**
```bash
# Check config
php artisan config:clear

# Verify keys are set
php artisan tinker
>>> config('api_auth.keys')
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Built with:
- [Laravel](https://laravel.com) - The PHP Framework
- [Laravel Breeze](https://laravel.com/docs/starter-kits#breeze) - Authentication scaffolding
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Alpine.js](https://alpinejs.dev) - Lightweight JavaScript framework
- [Vite](https://vitejs.dev) - Next generation frontend tooling

---

**Made with ❤️ by [Ahmad Karim](https://github.com/ahmu83)**
