<?php
namespace SpaceoRU\PurchaseVerifier;

use GuzzleHttp\Client;

/**
 * Trait HttpClientTrait
 * @package SpaceoRU\PurchaseVerifier
 */
trait HttpClientTrait
{
    /**
     * @return Client
     * @throws \InvalidArgumentException
     */
    public function httpClient(): Client
    {
        return new Client();
    }
}
