<?php
namespace SpaceoRU\PurchaseVerifier\Tests;

use SpaceoRU\PurchaseVerifier\Verifiers\AppleVerifier;

/**
 * Class PurchaseVerifierTest
 * @package SpaceoRU\PurchaseVerifier\Tests
 *
 * @coversDefaultClass \SpaceoRU\PurchaseVerifier\PurchaseVerifier
 */
class PurchaseVerifierTest extends TestCase
{
    /**
     * @throws \ReflectionException
     *
     * @covers ::createVerifier()
     */
    public function testCreateVerifier()
    {
        $verifier = $this->getVerifier();
        $factory = new \ReflectionMethod($verifier, 'createVerifier');
        $factory->setAccessible(true);

        $this->assertInstanceOf(AppleVerifier::class, $factory->invoke($verifier, 'apple'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported verifier [any]');

        $factory->invoke($verifier, 'any');
    }
}
