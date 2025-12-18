# Fresh clone test
cd /tmp
git clone https://github.com/ahmu83/dvc-laravel-starter-kit.git test-install
cd test-install

# Test installation
composer setup
# ✓ Dependencies install
# ✓ .env created
# ✓ Key generated
# ✓ Migrations run
# ✓ Assets built

# Test dev server
composer dev
# ✓ Server starts on :8000
# ✓ Queue worker starts
# ✓ Pail starts
# ✓ Vite HMR on :5173

# Test routes
curl http://localhost:8000
# ✓ Homepage loads

# Test API key generation
php artisan make:api-key
# ✓ Key generates

php artisan make:api-key --show-all
# ✓ 3 keys generate

# Test middleware
# Visit /sandbox routes (should redirect to login)
# Login and visit /sandbox (should work or ask for basic auth)

# Clean up
cd ..
rm -rf test-install


