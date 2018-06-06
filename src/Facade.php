<?php
namespace SpaceoRU\PurchaseVerifier;

/**
 * Class Facade
 * @package SpaceoRU\PurchaseVerifier
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return 'purchase.verifier';
    }
}
