<?php
namespace SpaceoRU\PurchaseVerifier\Contracts;

use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseNotReadyException;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseReceiptMalformed;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseVerificationException;

/**
 * Interface Verifier
 * @package SpaceoRU\PurchaseVerifier\Contracts
 */
interface Verifier
{
    /**
     * @param string $receipt
     * @param string $productId
     * @param bool $subscription
     * @return array
     * @throws \RuntimeException
     * @throws PurchaseVerificationException
     * @throws PurchaseNotReadyException
     * @throws PurchaseReceiptMalformed
     */
    public function verify(string $productId, string $receipt, bool $subscription = false): array;
}
