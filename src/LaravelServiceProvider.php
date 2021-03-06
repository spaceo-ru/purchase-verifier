<?php
namespace SpaceoRU\PurchaseVerifier;

/**
 * Class LaravelServiceProvider
 * @package SpaceoRU\PurchaseVerifier
 */
class LaravelServiceProvider extends \Illuminate\Support\ServiceProvider
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
