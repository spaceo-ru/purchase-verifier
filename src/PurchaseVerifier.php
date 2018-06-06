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
     * @param string $receipt Purchase Receipt (Base64)
     * @return void
     * @throws \LogicException
     */
    public function google(string $receipt)
    {
        throw new \LogicException('Google verification has not implemented yet');
    }

    /**
     * @param string $receipt Purchase Receipt (Base64)
     * @return void
     * @throws Exceptions\PurchaseVerificationException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function apple(string $receipt)
    {
        $this->createVerifier('apple')->verify($receipt);
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
