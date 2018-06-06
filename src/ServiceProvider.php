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
        $this->app->singleton('purchase.verifier', function (Application $app) {
            return new PurchaseVerifier($app);
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        //
    }
}
