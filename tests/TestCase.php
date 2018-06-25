<?php
namespace SpaceoRU\PurchaseVerifier\Tests;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use SpaceoRU\PurchaseVerifier\LaravelServiceProvider;
use SpaceoRU\PurchaseVerifier\PurchaseVerifier;

/**
 * Class TestCase
 * @package SpaceoRU\PurchaseVerifier\Tests
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = new Application();
        $app->setBasePath(realpath(__DIR__ . '/../'));
        $app->instance('config', new Repository());
        $app->register(LaravelServiceProvider::class);
        $app->boot();

        return $app;
    }

    /**
     * @return PurchaseVerifier
     */
    protected function getVerifier(): PurchaseVerifier
    {
        return app('purchase.verifier');
    }
}
