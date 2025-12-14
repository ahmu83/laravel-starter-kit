<?php

namespace App\Providers;

use App\Services\ViteAsset;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the ViteAsset service to the container
        $this->app->singleton('vite-asset', function () {
            return new ViteAsset;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerViteAssetDirective();
    }

    /**
     * Register the @viteAsset Blade directive for /app/build assets
     */
    private function registerViteAssetDirective()
    {
        // exit('aaa');
        Blade::directive('viteAsset', function ($expression) {
            return "<?php echo app('vite-asset')->render({$expression}); ?>";
        });
    }
}
