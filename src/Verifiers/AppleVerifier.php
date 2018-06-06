<?php
namespace SpaceoRU\PurchaseVerifier\Verifiers;

use GuzzleHttp\Exception\GuzzleException;
use SpaceoRU\PurchaseVerifier\Contracts\Verifier;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseVerificationException;
use SpaceoRU\PurchaseVerifier\HttpClientTrait;

/**
 * Class AppleVerifier
 * @package SpaceoRU\PurchaseVerifier\Verifiers
 */
class AppleVerifier implements Verifier
{
    use HttpClientTrait;

    /**
     * @param string $receipt
     * @throws PurchaseVerificationException
     * @throws \RuntimeException
     */
    public function verify(string $receipt)
    {
        $url = $this->environmentUrl();
        $payload = json_encode(['receipt-data' => $receipt], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $client = $this->httpClient();
            $response = $client->request('POST', $url, ['json' => $payload]);

            if (($httpCode = $response->getStatusCode()) !== 200) {
                throw new \RuntimeException(
                    'Verification service returned non 200 response code',
                    $httpCode
                );
            }

            $body = $response->getBody()->getContents();

            if (empty($body)) {
                throw new \RuntimeException(
                    'Verification service returned empty response',
                    500
                );
            }

            $body = json_decode($body, true);

            $status = $body['status'] ?? 500;
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        } catch (GuzzleException $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        }

        if ($status !== 0) {
            list($code, $message) = $this->statusDescription($status);

            throw new PurchaseVerificationException($message, $code);
        }
    }

    /**
     * @return string
     */
    protected function environmentUrl(): string
    {
        return config('app.debug', true)
            ? 'https://sandbox.itunes.apple.com/verifyReceipt'
            : 'https://buy.itunes.apple.com/verifyReceipt';
    }

    /**
     * @param int $status
     * @return array [code, description]
     */
    protected function statusDescription(int $status): array
    {
        switch ($status) {
            case 21000:
                $description = 'The App Store could not read the JSON object you provided.';
                break;

            case 21002:
                $description = 'The data in the receipt-data property was malformed or missing.';
                break;

            case 21003:
                $description = 'The receipt could not be authenticated.';
                break;

            case 21005:
                $description = 'The receipt server is not currently available.';
                break;

            case 21006:
                $description = 'This receipt is valid but the subscription has expired.';
                break;

            case 21007:
                $description = 'This receipt is from the test environment, ' .
                    'but it was sent to the production environment for verification.';
                break;

            case 21008:
                $description = 'This receipt is from the production environment, ' .
                    'but it was sent to the test environment for verification.';
                break;

            case 21010:
                $description = 'This receipt could not be authorized.';
                break;

            default:
                $description = 'Internal data access error.';
                break;
        }

        return [$status, $description];
    }
}
