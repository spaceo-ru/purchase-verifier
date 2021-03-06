<?php
namespace SpaceoRU\PurchaseVerifier\Verifiers;

use GuzzleHttp\Exception\GuzzleException;
use SpaceoRU\PurchaseVerifier\Contracts\Verifier;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseNotReadyException;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseReceiptMalformed;
use SpaceoRU\PurchaseVerifier\Exceptions\PurchaseVerificationException;
use SpaceoRU\PurchaseVerifier\HttpClientTrait;

/**
 * Class AppleVerifier
 * @package SpaceoRU\PurchaseVerifier\Verifiers
 */
class AppleVerifier implements Verifier
{
    use HttpClientTrait;

    const SANDBOX_URL = 'https://sandbox.itunes.apple.com/verifyReceipt';
    const PRODUCTION_URL = 'https://buy.itunes.apple.com/verifyReceipt';

    /**
     * @param string $productId
     * @param string $receipt
     * @param bool $subscription
     * @return array
     * @throws PurchaseNotReadyException
     * @throws PurchaseReceiptMalformed
     * @throws PurchaseVerificationException
     * @throws \RuntimeException
     */
    public function verify(string $productId, string $receipt, bool $subscription = false): array
    {
        try {
            $data = $subscription
                ? ['receipt-data' => $receipt, 'password' => config('purchase-verifier.apple.shared_secret')]
                : ['receipt-data' => $receipt];

            $response = $this->request($this->environmentUrl(), $data);

            // See: https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/StoreKitGuide/Chapters/AppReview.html#//apple_ref/doc/uid/TP40008267-CH10-SW1
            //
            // When validating receipts on your server, your server needs to be able
            // to handle a production-signed app getting its receipts from Apple’s test environment.
            //
            // The recommended approach is for your production server to
            // always validate receipts against the production App Store first.
            // If validation fails with the error code “Sandbox receipt used in production”,
            // validate against the test environment instead.
            //
            // The App Review team reviews apps in the sandbox.
            if ($response['status'] === 21007) {
                $response = $this->request(self::SANDBOX_URL, $data);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        } catch (GuzzleException $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        }

        $status = $response['status'] ?? 500;

        if ($status !== 0) {
            list($code, $message) = $this->statusDescription($status);

            throw new PurchaseVerificationException($message, $code);
        }

        $this->validatePurchase(
            $productId,
            $response['receipt'] ?? [],
            $subscription ? ($response['latest_receipt_info'] ?? []) : null
        );

        $latestReceipt = null;

        if (!empty($response['latest_receipt_info'])) {
            $latestReceipts = collect($response['latest_receipt_info'])
                ->where('product_id', $productId)
                ->sortBy('expires_date_ms');

            $latestReceipt = $latestReceipts->last();
        }

        return [
            'receipt' => $response['receipt'],
            'latest_receipt_info' => $latestReceipt
        ];
    }

    /**
     * @param string $url
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function request(string $url, array $data): array
    {
        $client = $this->httpClient();
        $response = $client->request('POST', $url, ['json' => $data]);

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

        return json_decode($body, true);
    }

    /**
     * @return string
     */
    protected function environmentUrl(): string
    {
        return config('app.debug', true)
            ? self::SANDBOX_URL
            : self::PRODUCTION_URL;
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

            case 21004:
                $description = 'The shared secret you provided does not match the shared secret on file for your account.';
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

    /**
     * @param string $productId
     * @param array $receipt
     * @param array|null $latestReceipts
     * @return bool
     * @throws PurchaseNotReadyException
     * @throws PurchaseReceiptMalformed
     */
    protected function validatePurchase(string $productId, array $receipt, array $latestReceipts = null): bool
    {
        $bundleId = config('purchase-verifier.apple.bundle_id');

        if (empty($receipt['bundle_id']) || $receipt['bundle_id'] !== $bundleId) {
            throw new PurchaseReceiptMalformed('Bundle ID is malformed');
        }

        if (empty($receipt['in_app'])) {
            throw new PurchaseNotReadyException('Seems like Apple has not recorded transaction yet. Try again later');
        }

        if (empty($receipt['in_app']) || !collect($receipt['in_app'])->contains('product_id', $productId)) {
            throw new PurchaseReceiptMalformed('Product ID is malformed');
        }

        if (null !== $latestReceipts) {
            if (empty($latestReceipts)) {
                throw new PurchaseNotReadyException('Subscription receipt does not have any transactions');
            }

            $latestReceipts = collect($latestReceipts)
                ->where('product_id', $productId)
                ->sortBy('expires_date_ms');

            $latest = $latestReceipts->last();

            if (!isset($latest['product_id'])) {
                throw new PurchaseReceiptMalformed('Product ID is malformed');
            }
        }

        return true;
    }
}
