<?php
namespace SpaceoRU\PurchaseVerifier;

use Illuminate\Foundation\Application;

/**
 * Class ServiceProvider
 * @package SpaceoRU\PurchaseVerifier
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
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

        $this->app->singleton('purchase.verifier', function (Application $app) {
            return new PurchaseVerifier($app);
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
