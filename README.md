# Laravel In-App Purchase Verifier

## Installation

Require this package with composer

```shell
composer require spaceo-ru/purchase-verifier
```

It uses Laravel Package Discovery, so does not require you to manually add the Service Provider.

## Usage

Development environment (Apple Sandbox) will be used if `APP_DEBUG` is true.

```php
$receipt = 'base64 encoded receipt';

try {
    \PurchaseVerifier::google($receipt);
    \PurchaseVerifier::apple($receipt);
} catch (PurcahseVerificationException $exception) {
    //
} catch (\RuntimeException $exception) {
    //
}
```
### Errors

RuntimeException will be thrown when there are general errors.
E.g.: could not connect to the verification service because of broken internet connection.

**Exception**: PurchaseVerificationException

**Apple**:
- 21000: The App Store could not read the JSON object you provided.
- 21002: The data in the receipt-data property was malformed or missing.
- 21003: The receipt could not be authenticated.
- 21005: The receipt server is not currently available.
- 21006: This receipt is valid but the subscription has expired.
- 21007: This receipt is from the test environment, but it was sent to the production environment for verification.
- 21008: This receipt is from the production environment, but it was sent to the test environment for verification.
- 21010: This receipt could not be authorized.
- 21100-21199: Internal data access error

## Testing

```shell
./vendor/bin/phpunit
```
