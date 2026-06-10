<div align="center">

# KosovoPay PHP SDK

**The official, strongly-typed PHP client for the [KosovoPay](https://kosovo.sh) payment API.**

📖 **API reference:** https://pay.kosovo.sh/docs

Built on [Saloon v3](https://docs.saloon.dev) · PHPStan level max · 100% typed request & response objects

[![PHP Version](https://img.shields.io/badge/php-%E2%89%A58.2-777BB4)](https://www.php.net/)
[![Static Analysis](https://img.shields.io/badge/PHPStan-level%20max-2a2a2a)](https://phpstan.org/)
[![Code Style](https://img.shields.io/badge/code%20style-Pint-FF2D20)](https://laravel.com/docs/pint)
[![License](https://img.shields.io/badge/license-KosovoPay%201.0-3da639)](LICENSE)

</div>

---

## Table of contents

- [Why this SDK](#why-this-sdk)
- [Requirements](#requirements)
- [Installation](#installation)
- [Authentication & client setup](#authentication--client-setup)
- [Quickstart](#quickstart)
- [Core concepts](#core-concepts)
  - [Minor units](#minor-units)
  - [Hosted vs. direct checkout](#hosted-vs-direct-checkout)
  - [Idempotency](#idempotency)
  - [API versioning](#api-versioning)
- [Payments](#payments)
- [Refunds](#refunds)
- [Banks](#banks)
- [Currencies & FX rates](#currencies--fx-rates)
- [Account (`me`)](#account-me)
- [Webhooks](#webhooks)
  - [Verifying a webhook](#verifying-a-webhook)
  - [Framework integration](#framework-integration)
  - [Event types](#event-types)
  - [Managing webhook endpoints](#managing-webhook-endpoints)
- [Helpers](#helpers)
  - [Money formatting & conversion](#money-formatting--conversion)
  - [Local amount validation](#local-amount-validation)
- [Error handling](#error-handling)
  - [Exception hierarchy](#exception-hierarchy)
  - [Error codes](#error-codes)
- [Type reference](#type-reference)
- [Retries, timeouts & resilience](#retries-timeouts--resilience)
- [Testing](#testing)
- [Development](#development)
- [Versioning & support](#versioning--support)
- [License](#license)

---

## Why this SDK

| | |
|---|---|
| **Typed end to end** | Every request is a `readonly` params object; every response is a `readonly` DTO with enum-typed fields. No associative-array guessing, no stringly-typed statuses. |
| **Forward compatible** | Unknown enum values — a bank or event type the platform adds *after* you ship — decode to an `Unknown` case instead of throwing. Old SDK versions keep working. |
| **Safe by construction** | Mutating calls carry an idempotency key automatically. Retries use exponential backoff, and a mutating `5xx` is **never** retried without an idempotency key — a network blip can't double-charge a customer. |
| **Typed errors** | The server error envelope maps to a precise exception class (`ValidationException`, `RateLimitException`, `PaymentException` subclasses, …), each carrying `errorCode`, `errorType`, `param`, `requestId` and `docUrl`. |
| **Statically verified** | PHPStan **level max**, Pint-clean, 29 tests covering every resource, idempotency, pagination, error mapping and webhook signature verification. |

---

## Requirements

- **PHP 8.2** or higher
- **ext-bcmath** *(optional)* — used for exact decimal FX math in [`Money::convert()`](#money-formatting--conversion); the SDK falls back to native float math when it is absent
- **ext-json** *(bundled with PHP)*

---

## Installation

```bash
composer require kosovopay/php-sdk
```

That's it — no service providers, no config publishing. The client is a plain object you construct with your API key.

---

## Authentication & client setup

Authenticate with a secret key from your [KosovoPay dashboard](https://kosovo.sh). Keys are environment-scoped — `sk_test_…` for the test sandbox, `sk_live_…` for production.

```php
use KosovoPay\KosovoPay;

$kp = new KosovoPay('sk_test_…');
```

The constructor accepts the full configuration surface; every argument after the key is optional and shown here with its default:

```php
$kp = new KosovoPay(
    apiKey:         'sk_live_…',
    baseUrl:        'https://api.kosovo.sh',   // override for a private/staging gateway
    apiVersion:     '2026-06-01',              // pinned via the Kosovopay-Version header
    connectTimeout: 10,                         // seconds to establish the TCP/TLS connection
    requestTimeout: 30,                         // seconds for the full request/response
    maxRetries:     3,                          // total attempts for retryable failures
);
```

> **Never hard-code a live key.** Load it from the environment or your secrets manager:
> ```php
> $kp = new KosovoPay($_ENV['KOSOVOPAY_SECRET_KEY']);
> ```

---

## Quickstart

Create a hosted checkout and redirect the buyer to KosovoPay's payment page:

```php
use KosovoPay\KosovoPay;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Params\CreatePaymentParams;

$kp = new KosovoPay($_ENV['KOSOVOPAY_SECRET_KEY']);

$payment = $kp->payments->create(new CreatePaymentParams(
    amount:      4990,                  // €49.90 in minor units
    currency:    CurrencyCode::EUR,
    successUrl:  'https://shop.test/thank-you',
    cancelUrl:   'https://shop.test/cart',
    description: 'Order #1024',
    metadata:    ['order_id' => '1024'],
));

header('Location: ' . $payment->hostedUrl);
exit;
```

When the buyer finishes, KosovoPay sends a [`payment.captured` webhook](#webhooks) and redirects them to your `successUrl`.

---

## Core concepts

### Minor units

**All monetary amounts are integers in the currency's minor unit** — cents for EUR/USD, etc. There are no floats anywhere in the money path, which eliminates rounding drift.

| Display | Pass as |
|---|---|
| €49.90 | `4990` |
| $5.00 | `500` |
| ¥1200 *(JPY, zero-decimal)* | `1200` |

Use [`Money::format()`](#money-formatting--conversion) to render a minor-unit integer back to a human string.

### Hosted vs. direct checkout

The SDK supports two checkout modes via the `CheckoutMode` enum:

| Mode | What happens | You use |
|---|---|---|
| `CheckoutMode::Hosted` *(default)* | KosovoPay renders the payment page and bank selection. | `$payment->hostedUrl` |
| `CheckoutMode::Direct` | **You** pick the bank up front; KosovoPay returns a bank redirect URL. Requires `bankCode`. | `$payment->redirectUrl` |

### Idempotency

Every mutating call (`payments->create`, `refunds->create`) accepts an optional idempotency key. **If you omit it, the SDK generates a ULID automatically**, so an in-flight retry never creates a duplicate charge. Supply your own key (e.g. your order ID) to make the operation idempotent across *your* retries too:

```php
$payment = $kp->payments->create($params, idempotencyKey: 'order-1024');
// Re-running this exact call returns the original payment instead of creating a second.
```

A key is valid for 24 hours. Reusing it with a **different** payload raises an [`IdempotencyException`](#exception-hierarchy).

### API versioning

The API is date-versioned. The SDK pins a version via the `Kosovopay-Version` header (default `2026-06-01`) so the response shape never changes underneath you. Upgrade deliberately by bumping `apiVersion` in the constructor after reading the changelog.

---

## Payments

### Create — hosted checkout

```php
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\CheckoutMode;
use KosovoPay\Params\CreatePaymentParams;
use KosovoPay\Params\LineItem;

$payment = $kp->payments->create(new CreatePaymentParams(
    amount:            4990,
    currency:          CurrencyCode::EUR,
    successUrl:        'https://shop.test/thank-you',
    mode:              CheckoutMode::Hosted,          // default — can be omitted
    cancelUrl:         'https://shop.test/cart',
    failUrl:           'https://shop.test/payment-failed',
    description:       'Order #1024',
    merchantReference: 'ORDER-1024',
    expiresAt:         time() + 1800,                 // optional: link expires in 30 min
    lineItems: [
        new LineItem(name: 'Wireless mouse', quantity: 1, unitAmountCents: 2990, sku: 'WM-01'),
        new LineItem(name: 'USB-C cable',    quantity: 2, unitAmountCents: 1000),
    ],
    metadata: ['order_id' => '1024', 'customer_tier' => 'gold'],
));

echo $payment->id;          // "pi_…"
echo $payment->hostedUrl;   // redirect the buyer here
```

### Create — direct checkout

You select the bank; KosovoPay returns a redirect straight to it. `bankCode` is **required** in this mode.

```php
use KosovoPay\Enums\BankCode;

$payment = $kp->payments->create(new CreatePaymentParams(
    amount:     4990,
    currency:   CurrencyCode::EUR,
    successUrl: 'https://shop.test/thank-you',
    mode:       CheckoutMode::Direct,
    bankCode:   BankCode::Onefor,
));

header('Location: ' . $payment->redirectUrl);
```

> The params object validates itself on construction. Passing `mode: Direct` without a `bankCode`, a non-positive `amount`, or a non-HTTP(S) `successUrl` throws `InvalidArgumentException` **before** any network call.

### Retrieve

```php
$payment = $kp->payments->retrieve('pi_1024');

echo $payment->status->value;        // "captured"
echo $payment->amountCaptured;       // 4990
echo $payment->createdAt()->format(DateTimeInterface::ATOM);
```

### List & paginate

`payments->all()` returns a **lazy iterator** that transparently walks every page using cursor pagination — you never touch `starting_after`:

```php
use KosovoPay\Enums\PaymentStatus;
use KosovoPay\Params\ListPaymentsParams;

$captured = $kp->payments->all(new ListPaymentsParams(
    status:     PaymentStatus::Captured,
    currency:   CurrencyCode::EUR,
    createdGte: strtotime('-30 days'),
    limit:      100,                     // page size; the iterator still spans all pages
));

foreach ($captured as $payment) {
    printf("%s  %d %s  %s\n", $payment->id, $payment->amount, $payment->currency->value, $payment->status->value);
}
```

Filter options on `ListPaymentsParams`: `status`, `bankCode`, `currency`, `merchantReference`, `createdGte`, `createdLte`, `limit`, `startingAfter`, `endingBefore`.

### Timeline

A chronological audit trail for a single payment, returned as a typed `Collection<TimelineEvent>`:

```php
foreach ($kp->payments->timeline('pi_1024') as $event) {
    printf("%s @ %d\n", $event->type, $event->at);
}
```

### Cancel

```php
$payment = $kp->payments->cancel('pi_1024', reason: 'customer_changed_mind');

assert($payment->status === PaymentStatus::Canceled);
```

Cancelling a payment that is no longer cancelable raises `PaymentNotCancelableException`.

---

## Refunds

### Create

Omit `amount` for a full refund; pass a value in minor units for a partial refund.

```php
use KosovoPay\Enums\RefundReason;
use KosovoPay\Params\CreateRefundParams;

// Full refund
$refund = $kp->refunds->create(new CreateRefundParams(payment: 'pi_1024'));

// Partial refund with a reason
$refund = $kp->refunds->create(new CreateRefundParams(
    payment: 'pi_1024',
    amount:  1000,                              // €10.00
    reason:  RefundReason::RequestedByCustomer,
), idempotencyKey: 'refund-order-1024-partial-1');

echo $refund->status->value;   // "succeeded" | "pending" | "failed"
```

> Not every bank supports **partial** refunds. Check `$bank->capabilities->refunds->partial` first (see [Banks](#banks)) — a partial refund to an unsupported bank raises `PartialRefundUnsupportedException`. Refunding more than the remaining balance raises `RefundExceedsRemainingException`.

### Retrieve

```php
$refund = $kp->refunds->retrieve('re_77');
```

### List

```php
use KosovoPay\Params\ListRefundsParams;

foreach ($kp->refunds->all(new ListRefundsParams(payment: 'pi_1024')) as $refund) {
    echo $refund->id, PHP_EOL;
}
```

---

## Banks

```php
use KosovoPay\Enums\BankCode;

// All banks enabled for your account, as a Collection<Bank>
$banks = $kp->banks->all();

foreach ($banks as $bank) {
    printf(
        "%-10s min %d step %d  partial-refunds: %s\n",
        $bank->code->value,
        $bank->capabilities->minAmount,
        $bank->capabilities->amountStep,
        $bank->capabilities->refunds->partial ? 'yes' : 'no',
    );
}

// A single bank
$onefor = $kp->banks->retrieve(BankCode::Onefor);

$onefor->capabilities->currencies;     // list<CurrencyCode> the bank accepts
$onefor->capabilities->minAmount;      // smallest accepted amount, minor units
$onefor->capabilities->amountStep;     // amount must be a multiple of this
$onefor->capabilities->refunds->supported;
$onefor->capabilities->refunds->partial;
```

Supported banks: `BankCode::Procredit`, `BankCode::Procard`, `BankCode::Onefor`.

---

## Currencies & FX rates

```php
use KosovoPay\Enums\CurrencyCode;

// Supported settlement currencies
foreach ($kp->currencies->all() as $currency) {
    printf("%s (%s) — %d decimals%s\n",
        $currency->code->value, $currency->symbol, $currency->decimals,
        $currency->isDefault ? ' [default]' : '');
}

// A live FX rate
$rate = $kp->rates->retrieve(CurrencyCode::EUR, CurrencyCode::USD);

echo $rate->rate;       // "1.0850" — a decimal string, never a lossy float
echo $rate->syncedAt;   // ISO-8601 timestamp of the last sync
var_dump($rate->stale); // true if the upstream feed is behind
```

Pair the rate with [`Money::convert()`](#money-formatting--conversion) for exact minor-unit conversion.

---

## Account (`me`)

Identify the key in use — its team, environment, usable banks and default currency:

```php
$me = $kp->me();

echo $me->team->name;                   // "Acme Store"
echo $me->mode->value;                  // "test" | "live"
echo $me->keyPrefix;                    // "sk_test_ab" — safe to log
$me->enabledBanks;                      // list<BankCode>
$me->defaultCurrency;                   // ?CurrencyCode
```

`keyPrefix` is the only key material safe to log — it identifies the key without exposing the secret.

---

## Webhooks

KosovoPay notifies your server of events (a captured payment, a succeeded refund) by `POST`ing a signed JSON event to your endpoint. **Always verify the signature** before trusting the payload.

### Verifying a webhook

```php
use KosovoPay\Webhook;
use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Exceptions\WebhookSignatureException;

$payload   = file_get_contents('php://input');                 // the RAW body — do not decode first
$signature = $_SERVER['HTTP_KOSOVOPAY_SIGNATURE'] ?? '';
$secret    = $_ENV['KOSOVOPAY_WEBHOOK_SECRET'];                 // whsec_… from the endpoint

try {
    $event = Webhook::constructEvent($payload, $signature, $secret);
} catch (WebhookSignatureException $e) {
    http_response_code(400);
    exit;
}

match ($event->type) {
    WebhookEventType::PaymentCaptured => fulfilOrder($event->asPayment()),
    WebhookEventType::PaymentFailed   => notifyFailure($event->asPayment()),
    WebhookEventType::RefundSucceeded => recordRefund($event->asRefund()),
    default                           => null,   // ignore everything else, stay forward-compatible
};

http_response_code(200);
```

`constructEvent()` does three things: verifies the HMAC-SHA256 signature in constant time, enforces a **5-minute replay window** on the timestamp, and decodes the raw body into a typed `Event`. The signed payload is `"{timestamp}.{raw_body}"` — which is why you must verify against the *unmodified* request body.

The signature header is exposed as a constant if you need it: `Webhook::SIGNATURE_HEADER` (`"Kosovopay-Signature"`). Adjust the replay tolerance with the fourth argument: `Webhook::constructEvent($payload, $sig, $secret, tolerance: 600)`.

The decoded `Event` exposes `id`, `type`, `created`, `livemode`, `apiVersion`, the raw `object`, and `previousAttributes`. Use `$event->asPayment()` / `$event->asRefund()` to hydrate the affected resource into its typed DTO, and `$event->createdAt()` for a `DateTimeImmutable`.

### Framework integration

**Laravel**

```php
use Illuminate\Http\Request;
use KosovoPay\Webhook;
use KosovoPay\Exceptions\WebhookSignatureException;

Route::post('/webhooks/kosovopay', function (Request $request) {
    try {
        $event = Webhook::constructEvent(
            payload:         $request->getContent(),
            signatureHeader: $request->header(Webhook::SIGNATURE_HEADER, ''),
            secret:          config('services.kosovopay.webhook_secret'),
        );
    } catch (WebhookSignatureException) {
        abort(400);
    }

    ProcessKosovoPayEvent::dispatch($event->id, $event->type->value);

    return response()->noContent();
});
```

**Symfony**

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use KosovoPay\Webhook;
use KosovoPay\Exceptions\WebhookSignatureException;

#[Route('/webhooks/kosovopay', methods: ['POST'])]
public function handle(Request $request): Response
{
    try {
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->headers->get(Webhook::SIGNATURE_HEADER, ''),
            $this->webhookSecret,
        );
    } catch (WebhookSignatureException) {
        return new Response('', 400);
    }

    // … handle $event …

    return new Response('', 200);
}
```

### Event types

| `WebhookEventType` | Wire value | `$event->as…()` |
|---|---|---|
| `PaymentCreated` | `payment.created` | `asPayment()` |
| `PaymentCaptured` | `payment.captured` | `asPayment()` |
| `PaymentFailed` | `payment.failed` | `asPayment()` |
| `PaymentCanceled` | `payment.canceled` | `asPayment()` |
| `PaymentExpired` | `payment.expired` | `asPayment()` |
| `RefundSucceeded` | `refund.succeeded` | `asRefund()` |
| `RefundFailed` | `refund.failed` | `asRefund()` |

Any event type added in the future arrives as `WebhookEventType::Unknown` — handle it with a `default` arm rather than a crash.

### Managing webhook endpoints

Register, list, rotate, and delete endpoints programmatically:

```php
use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Params\CreateWebhookEndpointParams;

// Create — the secret is returned exactly ONCE, on creation. Store it now.
$endpoint = $kp->webhookEndpoints->create(new CreateWebhookEndpointParams(
    url:           'https://shop.test/webhooks/kosovopay',
    enabledEvents: [WebhookEventType::PaymentCaptured, WebhookEventType::RefundSucceeded],
    description:   'Production fulfilment hook',
));

$secret = $endpoint->secret;   // "whsec_…" — persist this; it is never shown again

// List
foreach ($kp->webhookEndpoints->all() as $e) {
    printf("%s → %s [%s]\n", $e->id, $e->url, $e->status);
}

// Rotate the signing secret (returns the new secret once)
$rotated = $kp->webhookEndpoints->rotateSecret('we_1');
$newSecret = $rotated->secret;

// Delete
$deleted = $kp->webhookEndpoints->delete('we_1');
assert($deleted->deleted === true);
```

---

## Helpers

### Money formatting & conversion

```php
use KosovoPay\Money;

// Minor units → display string
Money::format(4990, decimals: 2, symbol: '€');     // "€49.90"
Money::formatCurrency(4990, $currencyDto);          // uses the Currency DTO's symbol + decimals

// Exact FX conversion (uses bcmath when available, rounds half-up to minor units)
$usd = Money::convert(4990, $rate->rate);           // 4990 EUR-cents × "1.0850" → 5414
```

### Local amount validation

Catch `amount_below_minimum` / `amount_step_invalid` **before** a round-trip by checking an amount against a bank's live capabilities:

```php
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\BankCode;

$check = $kp->validateAmount(125, CurrencyCode::EUR, BankCode::Onefor);

if (! $check->valid) {
    echo $check->code;          // "amount_below_minimum"
    echo $check->message;       // human-readable explanation
    print_r($check->nearestValid); // suggested valid amount(s), when applicable
}
```

---

## Error handling

Every non-2xx response is converted into a typed exception. Catch the specific subclass you care about, or the `KosovoPayException` base for a catch-all. Every exception carries the full error envelope.

```php
use KosovoPay\Exceptions\KosovoPayException;
use KosovoPay\Exceptions\ValidationException;
use KosovoPay\Exceptions\RateLimitException;
use KosovoPay\Exceptions\AuthenticationException;
use KosovoPay\Exceptions\Payment\AmountBelowMinimumException;

try {
    $payment = $kp->payments->create($params);
} catch (AmountBelowMinimumException $e) {
    // a precise, recoverable payment error
    return back()->withErrors(['amount' => $e->getMessage()]);
} catch (ValidationException $e) {
    // $e->param tells you which field was rejected
    return back()->withErrors([$e->param ?? 'request' => $e->getMessage()]);
} catch (RateLimitException $e) {
    sleep($e->retryAfter ?? 1);
    // … retry …
} catch (AuthenticationException $e) {
    Log::critical('KosovoPay key rejected', ['request_id' => $e->requestId]);
    throw $e;
} catch (KosovoPayException $e) {
    // catch-all — always log the request id for support
    Log::error($e->getMessage(), [
        'code'       => $e->errorCode,
        'type'       => $e->errorType,
        'status'     => $e->statusCode,
        'request_id' => $e->requestId,
        'doc_url'    => $e->docUrl,
    ]);
    throw $e;
}
```

Every `KosovoPayException` exposes:

| Property | Type | Meaning |
|---|---|---|
| `getMessage()` | `string` | Human-readable summary |
| `errorCode` | `?string` | Stable machine code, e.g. `amount_below_minimum` |
| `errorType` | `?string` | Error family, e.g. `validation_error` |
| `param` | `?string` | The offending request field, when applicable |
| `requestId` | `?string` | Correlation id — **always include this in support tickets** |
| `docUrl` | `?string` | Link to the docs for this error |
| `statusCode` | `int` | HTTP status |
| `retryAfter` *(RateLimitException only)* | `?int` | Seconds to wait before retrying |

### Exception hierarchy

```
KosovoPayException                        (abstract base — catch-all)
├── AuthenticationException               invalid / missing key
├── PermissionException                   authenticated but not allowed (403)
├── ValidationException                   malformed request (see ->param)
├── IdempotencyException                  key reused with a different payload
├── RateLimitException                    429 — exposes ->retryAfter
├── ApiException                          5xx / unclassified server error
└── PaymentException                      payment-domain failure
    ├── AmountBelowMinimumException
    ├── AmountStepInvalidException
    ├── BankNotEnabledException
    ├── BankUnreachableException
    ├── PaymentNotCancelableException
    ├── PaymentNotRefundableException
    ├── RefundExceedsRemainingException
    └── PartialRefundUnsupportedException

WebhookSignatureException                 (separate — thrown only by Webhook::constructEvent)
```

Resolution order: an exact `code` match wins; otherwise the error `type` family is used; otherwise it falls back to `ApiException`. An unrecognised code from a newer API never crashes the SDK.

### Error codes

| Code | Maps to | Notes |
|---|---|---|
| `missing_key`, `invalid_key` | `AuthenticationException` | |
| `invalid_request` | `ValidationException` | check `->param` |
| `resource_missing` | `ValidationException` | 404 |
| `unknown_api_version` | `ValidationException` | bad `Kosovopay-Version` |
| `currency_not_supported` | `ValidationException` | |
| `rate_unavailable` | `ValidationException` | FX feed down |
| `idempotency_payload_mismatch`, `idempotency_conflict` | `IdempotencyException` | |
| `rate_limited` | `RateLimitException` | honour `->retryAfter` |
| `amount_below_minimum` | `AmountBelowMinimumException` | |
| `amount_step_invalid` | `AmountStepInvalidException` | |
| `bank_not_enabled` | `BankNotEnabledException` | |
| `bank_unreachable` | `BankUnreachableException` | transient — retryable |
| `payment_not_cancelable` | `PaymentNotCancelableException` | |
| `payment_not_refundable` | `PaymentNotRefundableException` | |
| `refund_exceeds_remaining` | `RefundExceedsRemainingException` | |
| `partial_refund_unsupported` | `PartialRefundUnsupportedException` | |
| `internal_error` | `ApiException` | |

---

## Type reference

### Enums

| Enum | Cases |
|---|---|
| `CheckoutMode` | `Hosted`, `Direct` |
| `BankMode` | `Test`, `Live` |
| `BankCode` | `Procredit`, `Procard`, `Onefor`, `Unknown` |
| `CurrencyCode` | The full ISO 4217 circulating set (155 currencies) — `EUR`, `USD`, `GBP`, `JPY`, `CHF`, `CNY`, `AUD`, `CAD`, … `ALL`, `RSD`, `MKD`, plus `Unknown`. Each case's value is its ISO code. |
| `PaymentStatus` | `Pending`, `Authorized`, `Captured`, `PartiallyRefunded`, `Refunded`, `Failed`, `Canceled`, `Unknown` |
| `RefundStatus` | `Pending`, `Succeeded`, `Failed`, `Unknown` |
| `RefundReason` | `RequestedByCustomer`, `Duplicate`, `Fraudulent`, `Other` |
| `WebhookEventType` | `PaymentCreated`, `PaymentCaptured`, `PaymentFailed`, `PaymentCanceled`, `PaymentExpired`, `RefundSucceeded`, `RefundFailed`, `Unknown` |

Enums marked with `Unknown` are **forward-compatible**: any value the platform introduces later decodes to `Unknown` rather than throwing. Always include a `default`/`Unknown` arm when matching on them.

### Key response objects

**`Payment`** — `id`, `status: PaymentStatus`, `mode: BankMode`, `amount`, `amountCaptured`, `amountRefunded`, `currency: CurrencyCode`, `bankCode: ?BankCode`, `merchantReference`, `description`, `payer: ?Payer`, `lineItems`, `metadata`, `fx: ?Fx`, `lastError`, `expires`, `captured`, `created`, `refunds`, `checkoutMode: ?CheckoutMode`, `hostedUrl`, `redirectUrl` · methods: `createdAt(): DateTimeImmutable`

**`Refund`** — `id`, `payment`, `amount`, `status: RefundStatus`, `reason: ?RefundReason`, `failureReason`, `created`, `succeededAt` · methods: `createdAt(): ?DateTimeImmutable`

**`Bank`** — `code: BankCode`, `displayName`, `logoUrl`, `enabled`, `modes`, `capabilities: BankCapabilities`
**`BankCapabilities`** — `currencies: list<CurrencyCode>`, `minAmount`, `amountStep`, `refunds: RefundCapability`
**`RefundCapability`** — `supported: bool`, `partial: bool`

**`Currency`** — `code: CurrencyCode`, `name`, `symbol`, `decimals`, `isDefault`
**`Rate`** — `from: CurrencyCode`, `to: CurrencyCode`, `rate: string`, `syncedAt`, `stale`
**`Me`** — `team: Team`, `mode: BankMode`, `keyPrefix`, `enabledBanks: list<BankCode>`, `defaultCurrency: ?CurrencyCode`
**`WebhookEndpoint`** — `id`, `url`, `description`, `enabledEvents: list<WebhookEventType>`, `status`, `mode: BankMode`, `created`, `secret`
**`Event`** — `id`, `type: WebhookEventType`, `created`, `livemode`, `apiVersion`, `data`, `object`, `previousAttributes` · methods: `asPayment()`, `asRefund()`, `createdAt()`

Single-page lists (`banks`, `currencies`, `webhookEndpoints`, payment `timeline`) return a typed **`Collection<T>`** implementing `Countable` and `IteratorAggregate` — iterate it directly, call `->count()`, or grab `->all()` / `->data` for the array. Paginated lists (`payments`, `refunds`) return a **lazy iterator** instead.

---

## Retries, timeouts & resilience

The connector retries transient failures with exponential backoff, governed by `maxRetries` (default `3`) and an internal `500 ms` base interval (→ ~0.5s, 1s, 2s).

| Failure | Retried? |
|---|---|
| Network / connection error | ✅ always |
| `429 Too Many Requests` | ✅ always |
| `5xx` on a `GET`/`HEAD` | ✅ |
| `5xx` on a mutating call **with** an idempotency key | ✅ (safe — the key dedupes) |
| `5xx` on a mutating call **without** an idempotency key | ❌ (could double-charge) |
| `4xx` (validation, auth, etc.) | ❌ (deterministic — won't change) |

Because the SDK auto-attaches an idempotency key to every mutating call, your `create` operations are retried safely out of the box.

Tune timeouts via the constructor (`connectTimeout`, `requestTimeout`).

---

## Testing

The client is backed by a Saloon connector, so you can swap in a `MockClient` and assert against requests — no network, fully deterministic:

```php
use KosovoPay\KosovoPay;
use KosovoPay\Requests\Payments\CreatePayment;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

$mock = new MockClient([
    CreatePayment::class => MockResponse::make([
        'object' => 'payment', 'id' => 'pi_test', 'status' => 'pending', 'mode' => 'test',
        'amount' => 4990, 'amount_captured' => 0, 'amount_refunded' => 0, 'currency' => 'EUR',
        'created' => 1749600000, 'refunds' => [],
    ], 201),
]);

$kp = new KosovoPay('sk_test_x');
$kp->connector()->withMockClient($mock);

$payment = $kp->payments->create($params);

$mock->assertSent(fn (Request $r) => $r->headers()->get('Idempotency-Key') !== null);
```

---

## Development

```bash
composer install

composer test        # Pest test suite
composer stan        # PHPStan, level max
composer lint        # Pint, dry run
composer fix         # Pint, apply fixes
```

The codebase is held to **PHPStan level max with zero suppressions** — no `@phpstan-ignore`, no `assert()`-to-silence, no blind casts. Decoded JSON is narrowed through a dedicated coercion layer so types are real, not asserted.

---

## Versioning & support

- The SDK follows **semantic versioning**. Breaking changes only land in a new major.
- The **API** is date-versioned independently and pinned via the `Kosovopay-Version` header — your integration won't shift under you when the platform evolves.
- Found a bug or need help? Include the `requestId` from the relevant `KosovoPayException` — it lets support trace the exact call.

---

## License

**KosovoPay License 1.0** — free to use, including commercially, at no charge.
Modifying, forking, redistributing, or reverse-engineering the SDK is **not**
permitted; it is maintained solely by KosovoPay. See [LICENSE](LICENSE).
