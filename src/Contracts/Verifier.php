<?php
namespace SpaceoRU\PurchaseVerifier\Contracts;

use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseVerificationException;

/**
 * Interface Verifier
 * @package SpaceoRU\PurchaseVerifier\Contracts
 */
interface Verifier
{
    /**
     * @param string $receipt
     * @throws \RuntimeException
     * @throws PurchaseVerificationException
     */
    public function verify(string $receipt);
}
