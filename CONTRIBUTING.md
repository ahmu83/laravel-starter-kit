# Contributing to Laravel Starter Kit

Thank you for considering contributing to this project! 

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue with:
- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- Laravel version, PHP version
- Any error messages or logs

### Suggesting Features

Feature requests are welcome! Please:
- Check existing issues first
- Explain the use case
- Describe the expected behavior
- Consider backward compatibility

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```

3. **Make your changes**
   - Follow Laravel coding standards
   - Add tests if applicable
   - Update documentation

4. **Test your changes**
   ```bash
   composer test
   ./vendor/bin/php-cs-fixer fix
   ```

5. **Commit your changes**
   ```bash
   git commit -m 'Add some amazing feature'
   ```

6. **Push to your fork**
   ```bash
   git push origin feature/amazing-feature
   ```

7. **Open a Pull Request**
   - Describe what you changed and why
   - Reference any related issues

## Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/laravel-starter-kit.git
cd laravel-starter-kit

# Install dependencies
composer setup

# Run development server
composer dev

# Run tests
composer test
```

## Code Style

This project follows:
- PSR-12 coding standard
- Laravel best practices
- Strict types where applicable

Format your code:
```bash
./vendor/bin/php-cs-fixer fix
```

## Testing

All new features should include tests:
```bash
php artisan test --filter=YourNewFeatureTest
```

## Documentation

Please update relevant documentation:
- README.md - If adding major features
- MIDDLEWARE.md - If adding/changing middleware
- SECURITY.md - If affecting security
- Inline comments - For complex logic

## Questions?

Feel free to:
- Open an issue for discussion
- Tag @ahmu83 in your PR
- Ask in the Discussions section

---

Thank you for contributing! 🎉
