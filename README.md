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
$productId = '...';

try {
    $verified = \PurchaseVerifier::apple($productId, $receipt, $isSubscriptionReceipt);
} catch (PurcahseVerificationException $exception) {
    //
} catch (PurchaseReceiptMalformed $exception) {
    //
} catch (PurchaseNotReadyException $exception) {
    //
} catch (\RuntimeException $exception) {
    //
}
```

`$verified` is array contains two keys:
- `receipt` equals to https://developer.apple.com/library/archive/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
- `latest_receipt_info` contains details of a auto-renewable subscription transactions (or null, if there no any)

Example `latest_receipt_info` (*Note*: Apple returns every field as string)
```
{
    product_id: 'com.example.monthly',
    transaction_id: '10000...', // Latest transaction
    original_transaction_id: '10000...', // Indicates the first transaction (where a user actually purchased a product)
    purchase_date: '2018-06-27 08:00:04 Etc/GMT',
    purchase_date_ms: '1530086404000', // UTC Milliseconds
    purchase_date_pst: '2018-06-27 01:00:04 America/Los_Angeles',
    original_purchase_date: '2018-06-27 06:40:05 Etc/GMT',
    original_purchase_date_ms: '1530081605000', UTC Milliseconds
    original_purchase_date_pst: '2018-06-26 23:40:05 America/Los_Angeles',
    expires_date: '2018-06-27 08:05:04 Etc/GMT',
    expires_date_ms: '1530086704000', UTC Milliseconds
    expires_date_pst: '2018-06-27 01:05:04 America/Los_Angeles',
    web_order_line_item_id: '10000...',
    is_trial_period: 'false',
    is_in_intro_offer_period: 'false'
}
```

#### Subscription

If the subscription expiration date (`latest_receipt_info.expires_date`) is a past date, it is safe to assume that the subscription has expired.

In case of validating subscriptions periodically you should save receipt details. Example model:
```
{
    userId: 1, // Your app's customer id
    os: "ios" | "android",
    receipt: "base64 encoded receipt", // Original receipt that was sent from the mobile app
    productId: "...",
    originalTransactionId: "...", // latest_receipt_info.original_transaction_id
    latestTransactionId: "...", // latest_receipt_info.transaction_id
    expiresAt: "...", // latest_receipt_info.expires_date*,
    is_active: true
}
```

Then schedule a command which will query records every day:
```php
public function handle(): void
{
    $subscriptions = Subscription::where('is_active', true)->get();
    
    foreach ($subscriptions as $subscription) {
        try {
            $verified = \PurchaseVerifier::apple(
                $subscription->productId,
                $subscription->receipt,
                true
            );
        } catch (PurcahseVerificationException | PurchaseReceiptMalformed | PurchaseNotReadyException $exception) {
            $subscription->is_active = false;
            $subscription->save();
            
            // disable user features etc
        } catch (\RuntimeException $exception) {
            // maybe it is internet connection issue, perhaps you would like to try later
        }
    }
}
```

### Errors

`RuntimeException` will be thrown when there are general errors.
E.g.: when your server could not connect to the verification service because of broken internet connection.

`PurchaseNotReadyException` will be thrown when the transaction has not recorded by Apple yet. 
It means your client should retry the request later.

`PurchaseReceiptMalformed` will be thrown when the receipt is broken.
E.g.: `$productId` does not match returned `product_id` from Apple.

`PurcahseVerificationException` will be thrown when Apple returns non-zero code (see below).

**Exception**: PurchaseVerificationException

**Apple**:
- 21000: The App Store could not read the JSON object you provided.
- 21002: The data in the receipt-data property was malformed or missing.
- 21003: The receipt could not be authenticated.
- 21004: TThe shared secret you provided does not match the shared secret on file for your account.
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
