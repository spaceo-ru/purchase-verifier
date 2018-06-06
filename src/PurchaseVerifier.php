<?php
namespace SpaceoRU\PurchaseVerifier;

use Illuminate\Foundation\Application;
use SpaceoRU\PurchaseVerifier\Contracts\Verifier;
use SpaceoRU\PurchaseVerifier\Verifiers\AppleVerifier;

/**
 * Class PurchaseVerifier
 * @package SpaceoRU\PurchaseVerifier
 */
class PurchaseVerifier
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $productId
     * @param string $receipt
     * @return void
     * @throws \LogicException
     */
    public function google(string $productId, string $receipt)
    {
        throw new \LogicException('Google verification has not implemented yet');
    }

    /**
     * @param string $productId
     * @param string $receipt
     * @return array
     * @throws Exceptions\PurchaseNotReadyException
     * @throws Exceptions\PurchaseReceiptMalformed
     * @throws Exceptions\PurchaseVerificationException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function apple(string $productId, string $receipt): array
    {
        return $this->createVerifier('apple')->verify($productId, $receipt);
    }

    /**
     * @param string $verifier
     * @return Verifier
     * @throws \InvalidArgumentException
     */
    protected function createVerifier(string $verifier): Verifier
    {
        if ($verifier === 'apple') {
            return new AppleVerifier();
        }

        throw new \InvalidArgumentException("Unsupported verifier [{$verifier}]");
    }
}
