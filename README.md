# Laravel Starter Kit

A modern Laravel 12 starter kit with authentication, organized asset structure, and developer-friendly middleware.

## Features

- ✅ Laravel 12 with Breeze authentication
- ✅ Organized asset structure (pages, components, layouts)
- ✅ Custom middleware (BasicAuth, Sandbox, Request Logging)
- ✅ Vite with glob patterns for flexible asset compilation
- ✅ Database table prefixing support
- ✅ CSRF webhook exemptions
- ✅ Sandbox routes with email allowlist
- ✅ Tailwind CSS 3.x
- ✅ Alpine.js included

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/MariaDB or PostgreSQL

### Quick Start

1. **Clone the repository**
```bash
   git clone https://github.com/yourusername/laravel-starter-kit.git
   cd laravel-starter-kit
```

2. **Install dependencies**
```bash
   composer setup
```
   
   This runs:
   - `composer install`
   - Copies `.env.example` to `.env`
   - Generates application key
   - Runs migrations
   - Installs npm packages
   - Builds assets

3. **Configure environment**
```bash
   # Edit .env with your database credentials
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
```

4. **Start development server**
```bash
   composer dev
```
   
   This runs concurrently:
   - Laravel development server (port 8000)
   - Queue worker
   - Log viewer (Pail)
   - Vite dev server (HMR)

## Project Structure

### Asset Organization
```
resources/assets/
├── css/
│   └── app.css          # Main stylesheet
├── js/
│   ├── app.js           # Main JavaScript
│   └── bootstrap.js     # Axios & dependencies
├── pages/               # Page-specific assets
├── components/          # Component-specific assets
└── layouts/             # Layout-specific assets
```

Vite automatically compiles all `.css`, `.scss`, and `.js` files in these directories.

### Routes

- `routes/web.php` - Main application routes
- `routes/auth.php` - Authentication routes (login, register, etc.)
- `routes/sandbox.php` - Protected development routes
- `routes/api.php` - API routes
- `routes/web-redirects.php` - URL redirects

### Custom Middleware

#### BasicAuth
HTTP Basic Authentication for staging/sandbox environments.
```php
// Skips in local environment
// Configure in .env:
BASIC_AUTH_USER=admin
BASIC_AUTH_PASS=secure_password
```

#### SandboxMiddleware
Email-based allowlist for sandbox routes.
```php
// Configure allowed emails:
SANDBOX_ALLOWED_EMAILS=user1@example.com,user2@example.com
```

#### Log
Attaches unique UUID to each request for debugging.
```php
// Access in logs or responses:
$request->attributes->get('log_uuid')
```

## Database

### Table Prefixing

All tables are prefixed with `laravel_` by default.
```env
DB_PREFIX=laravel_
```

To change the prefix, update `.env`:
```env
DB_PREFIX=myapp_
```

## Sandbox Routes

Protected routes for development/internal tools:
```
/sandbox/           # Returns status info
/sandbox/ping       # Simple ping endpoint
```

Access requires:
1. Authentication (logged in user)
2. Email in allowlist (production)
3. Basic auth credentials (if configured)

## Development Commands
```bash
# Run all development services
composer dev

# Run tests
composer test

# Code formatting
./vendor/bin/php-cs-fixer fix
```

## Deployment

### Build Assets
```bash
npm run build
```

### Environment Setup

Ensure these are set in production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Set strong credentials
BASIC_AUTH_USER=...
BASIC_AUTH_PASS=...

# Configure mail
MAIL_MAILER=smtp
MAIL_HOST=...
```

## WordPress Integration (Optional)

This starter kit can be integrated with WordPress using the [WP Laravel Loader Plugin](https://github.com/yourusername/wp-laravel-loader).

### Setup for WordPress

1. **Install in WordPress directory**
```bash
   # Install Laravel in: /path/to/wordpress/laravel/
   # Public path will be: /path/to/wordpress/app/
```

2. **Update Vite configuration**
```javascript
   // vite.config.js
   publicDirectory: '../app',  // Change from './public'
```

3. **Update environment**
```env
   ASSET_URL=https://yourdomain.com/app
```

4. **Install WP Laravel Loader plugin** in WordPress

See [WP Laravel Loader documentation](https://github.com/yourusername/wp-laravel-loader) for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Built with:
- [Laravel](https://laravel.com)
- [Laravel Breeze](https://laravel.com/docs/starter-kits#breeze)
- [Tailwind CSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [Vite](https://vitejs.dev)
```

#### 5. **Add LICENSE File**

Create `LICENSE` (MIT):
```
MIT License

Copyright (c) 2024 [Your Name]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

#### 6. **Add .gitattributes**

Create `.gitattributes` for proper language detection:
```
* text=auto

*.blade.php linguist-language=PHP
*.css linguist-language=CSS
*.scss linguist-language=SCSS
*.js linguist-language=JavaScript

/tests export-ignore
/.github export-ignore
