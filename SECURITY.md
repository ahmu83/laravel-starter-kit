# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in this starter kit, please send an email to **your-email@example.com** (replace with your actual email).

**Please do not open public issues for security vulnerabilities.**

We take security seriously and will respond to valid reports as quickly as possible.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Security Features

This starter kit includes several security features out of the box:

### Authentication & Authorization
- ✅ Laravel Breeze authentication with secure password hashing (bcrypt)
- ✅ Email verification support
- ✅ Password reset functionality with secure tokens
- ✅ Rate limiting on authentication routes (5 attempts per minute)
- ✅ Remember me functionality with secure tokens

### Request Security
- ✅ CSRF protection on all routes (with webhook exemptions)
- ✅ HTTP Basic Authentication for sandbox routes
- ✅ Email allowlist for sensitive sandbox routes
- ✅ Request logging with unique UUIDs for tracking

### Session Security
- ✅ Secure session configuration
- ✅ HTTP-only cookies (prevent XSS access)
- ✅ SameSite cookie protection
- ✅ Database-backed sessions

### Middleware Protection
- ✅ `BasicAuth` - HTTP Basic Authentication for staging environments
- ✅ `SandboxMiddleware` - Email-based access control for internal routes
- ✅ `VerifyCsrfToken` - CSRF protection with webhook exemptions

### Database Security
- ✅ Table prefixing support (prevents conflicts)
- ✅ Prepared statements (Laravel Query Builder/Eloquent)
- ✅ Mass assignment protection

## Security Best Practices

When deploying this starter kit, ensure you:

1. **Set strong environment variables:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<strong-32-character-key>
   BASIC_AUTH_PASS=<strong-password>
   ```

2. **Configure HTTPS:**
   - Force HTTPS in production
   - Set `SESSION_SECURE_COOKIE=true`
   - Use HSTS headers

3. **Restrict sandbox access:**
   ```env
   SANDBOX_ALLOWED_EMAILS=admin@yourdomain.com,dev@yourdomain.com
   ```

4. **Keep dependencies updated:**
   ```bash
   composer update
   npm update
   ```

5. **Configure proper file permissions:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

6. **Use strong database credentials:**
   - Never use default passwords
   - Use least privilege principle for database users

7. **Configure rate limiting** for API routes in `bootstrap/app.php`

## Webhook Security

The starter kit excludes `/webhooks/*` from CSRF verification. When implementing webhooks:

- ✅ Verify webhook signatures from the service provider
- ✅ Use shared secrets or API keys
- ✅ Validate IP addresses when possible
- ✅ Log all webhook attempts
- ✅ Implement replay attack prevention

Example:
```php
// In your webhook controller
if (!$this->verifySignature($request)) {
    abort(403, 'Invalid signature');
}
```

## Known Security Considerations

### Basic Auth in Local Environment
The `BasicAuth` middleware is **disabled in local environment** for development convenience. This is intentional but should be understood:

```php
// BasicAuth.php
if (app()->environment('local')) {
    return $next($request);  // Skips authentication
}
```

Ensure `APP_ENV=production` is set in staging/production environments.

### Sandbox Routes
Sandbox routes at `/sandbox/*` are protected by:
1. Authentication requirement
2. Email allowlist (production)
3. Basic authentication (if configured)

Keep the allowlist restricted to trusted team members only.

## Compliance & Standards

This starter kit follows:
- OWASP Top 10 security guidelines
- Laravel security best practices
- PSR-12 coding standards

## Security Checklist for Deployment

Before deploying to production:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated
- [ ] HTTPS configured
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Strong database password
- [ ] `BASIC_AUTH_PASS` changed from default
- [ ] `SANDBOX_ALLOWED_EMAILS` configured
- [ ] All dependencies updated
- [ ] File permissions set correctly
- [ ] Logs are not publicly accessible
- [ ] `.env` file is not version controlled

## Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
