<?php
namespace SpaceoRU\PurchaseVerifier;

if (!function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

/**
 * Class LumenServiceProvider
 * @package SpaceoRU\PurchaseVerifier
 */
class LumenServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @return array
     */
    public function provides(): array
    {
        return ['purchase.verifier'];
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/purchase-verifier.php', 'purchase-verifier');

        $this->app->singleton('purchase.verifier', function () {
            return new PurchaseVerifier();
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/purchase-verifier.php' => config_path('purchase-verifier.php'),
        ]);
    }
}
