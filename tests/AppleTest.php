<?php
namespace SpaceoRU\PurchaseVerifier\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Mockery\MockInterface;
use SpaceoRU\PurchaseVerifier\Contracts\Verifier;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseVerificationException;
use SpaceoRU\PurchaseVerifier\Verifiers\AppleVerifier;

/**
 * Class AppleTest
 * @package SpaceoRU\PurchaseVerifier\Tests
 *
 * @coversDefaultClass \SpaceoRU\PurchaseVerifier\Verifiers\AppleVerifier
 */
class AppleTest extends TestCase
{
    /**
     * @throws \ReflectionException
     *
     * @covers ::environmentUrl()
     */
    public function testEnvironmentUrl()
    {
        $verifier = new AppleVerifier();
        $url = new \ReflectionMethod($verifier, 'environmentUrl');
        $url->setAccessible(true);

        config(['app.debug' => true]);
        $this->assertEquals('https://sandbox.itunes.apple.com/verifyReceipt', $url->invoke($verifier));

        config(['app.debug' => false]);
        $this->assertEquals('https://buy.itunes.apple.com/verifyReceipt', $url->invoke($verifier));
    }

    /**
     * @throws \ReflectionException
     *
     * @covers ::statusDescription()
     */
    public function testStatusDescription()
    {
        $statuses = [
            21000 => 'The App Store could not read the JSON object you provided.',
            21002 => 'The data in the receipt-data property was malformed or missing.',
            21003 => 'The receipt could not be authenticated.',
            21005 => 'The receipt server is not currently available.',
            21006 => 'This receipt is valid but the subscription has expired.',
            21007 => 'This receipt is from the test environment, but it was sent to the production environment for verification.',
            21008 => 'This receipt is from the production environment, but it was sent to the test environment for verification.',
            21010 => 'This receipt could not be authorized.',
            99999 => 'Internal data access error.'
        ];

        $verifier = new AppleVerifier();
        $method = new \ReflectionMethod($verifier, 'statusDescription');
        $method->setAccessible(true);

        foreach ($statuses as $code => $description) {
            $this->assertEquals([$code, $description], $method->invoke($verifier, $code));
        }
    }

    /**
     * @covers ::request()
     * @throws \ReflectionException
     */
    public function testAppleReturnNonSuccessHttpCode()
    {
        list($verifier, $httpClient, $response) = $this->mock();

        $response->shouldReceive('getStatusCode')->andReturn(400);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Verification service returned non 200 response code');

        $this->requestMethod()->invoke($verifier, $this->environmentUrl(), 'receipt');
    }

    /**
     * @covers ::request()
     * @throws \ReflectionException
     */
    public function testAppleReturnEmptyBody()
    {
        list($verifier, $httpClient, $response) = $this->mock();

        $body = \Mockery::mock(Stream::class);

        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($body);

        $body->shouldReceive('getContents')->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Verification service returned empty response');

        $this->requestMethod()->invoke($verifier, $this->environmentUrl(), 'receipt');
    }

    /**
     * @covers ::request()
     * @throws \ReflectionException
     */
    public function testRequestReturnBodyPayload()
    {
        list($verifier, $httpClient, $response) = $this->mock();

        $body = \Mockery::mock(Stream::class);

        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($body);

        $body->shouldReceive('getContents')->andReturn(json_encode($payload = [
            'status' => 0,
            'receipt' => [
                'key' => 'value'
            ]
        ]));

        $this->assertEquals($payload, $this->requestMethod()->invoke($verifier, $this->environmentUrl(), 'receipt'));
    }

    /**
     * @covers ::verify()
     */
    public function testAppleReturnNonSuccessStatus()
    {
        list($verifier, $httpClient, $response) = $this->mock();

        $body = \Mockery::mock(Stream::class);

        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($body);

        $body->shouldReceive('getContents')->andReturn(json_encode([
            'status' => 999
        ]));

        $this->expectException(PurchaseVerificationException::class);
        $this->expectExceptionCode(999);
        $this->expectExceptionMessage('Internal data access error.');

        $verifier->verify('receipt');
    }

    /**
     * @covers ::verify()
     */
    public function testAppleReturnSuccessStatus()
    {
        list($verifier, $httpClient, $response) = $this->mock();

        $body = \Mockery::mock(Stream::class);

        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($body);

        $body->shouldReceive('getContents')->andReturn(json_encode([
            'status' => 0
        ]));

        $verifier->verify('receipt');
    }

    /**
     * @covers ::verify()
     * @throws \Exception
     */
    public function testSwitchEnvironmentFromProductionToSandbox()
    {
        config(['app.debug' => false]);

        /** @var AppleVerifier|MockInterface $verifier */
        $verifier = \Mockery::mock(AppleVerifier::class . '[request]');
        $verifier->shouldAllowMockingProtectedMethods();

        $verifier->shouldReceive('request')->withArgs(function ($url, $receipt) {
            return [$url, $receipt] === [$this->environmentUrl(), 'receipt'];
        })->once()->andReturn(['status' => 21007]);

        config(['app.debug' => true]);

        $verifier->shouldReceive('request')->withArgs(function ($url, $receipt) {
            return [$url, $receipt] === [$this->environmentUrl(), 'receipt'];
        })->once()->andReturn(['status' => 0]);

        $verifier->verify('receipt');
    }

    /**
     * @covers ::verify()
     * @throws \Exception
     */
    public function testSwitchEnvironmentFromSandboxToProduction()
    {
        config(['app.debug' => true]);

        /** @var AppleVerifier|MockInterface $verifier */
        $verifier = \Mockery::mock(AppleVerifier::class . '[request]');
        $verifier->shouldAllowMockingProtectedMethods();

        $verifier->shouldReceive('request')->withArgs(function ($url, $receipt) {
            return [$url, $receipt] === [$this->environmentUrl(), 'receipt'];
        })->once()->andReturn(['status' => 21008]);

        config(['app.debug' => false]);

        $verifier->shouldReceive('request')->withArgs(function ($url, $receipt) {
            return [$url, $receipt] === [$this->environmentUrl(), 'receipt'];
        })->once()->andReturn(['status' => 0]);

        $verifier->verify('receipt');
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    private function environmentUrl(): string
    {
        $verifier = new AppleVerifier();
        $url = new \ReflectionMethod($verifier, 'environmentUrl');
        $url->setAccessible(true);

        return $url->invoke($verifier);
    }

    /**
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    private function requestMethod(): \ReflectionMethod
    {
        $method = new \ReflectionMethod(AppleVerifier::class, 'request');
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @return array
     */
    private function mock(): array
    {
        config(['app.debug' => true]);

        /** @var Verifier|\Mockery\MockInterface $verifier */
        $verifier = \Mockery::mock(AppleVerifier::class . '[httpClient]');
        $httpClient = \Mockery::mock(Client::class);
        $response = \Mockery::mock(Response::class);

        $verifier->shouldReceive('httpClient')->andReturn($httpClient);

        $httpClient->shouldReceive('request')->withArgs(function ($method, $url, $options) {
            return [$method, $url, $options] === [
                'POST',
                'https://sandbox.itunes.apple.com/verifyReceipt',
                ['json' => json_encode(['receipt-data' => 'receipt'])]
            ];
        })->andReturn($response);

        return [$verifier, $httpClient, $response];
    }
}
