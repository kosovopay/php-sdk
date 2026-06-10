<?php

declare(strict_types=1);

use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Exceptions\AuthenticationException;
use KosovoPay\Exceptions\Payment\PartialRefundUnsupportedException;
use KosovoPay\Exceptions\PermissionException;
use KosovoPay\Exceptions\RateLimitException;
use KosovoPay\Exceptions\ValidationException;
use KosovoPay\Params\CreateRefundParams;
use KosovoPay\Requests\Currencies\RetrieveRate;
use KosovoPay\Requests\Refunds\CreateRefund;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function err(string $type, string $code, int $status, array $headers = []): MockResponse
{
    return MockResponse::make([
        'error' => ['type' => $type, 'code' => $code, 'message' => 'nope', 'param' => 'x', 'request_id' => 'req_1', 'doc_url' => 'https://docs/'.$code],
    ], $status, $headers);
}

it('maps a 422 validation_error to ValidationException with envelope fields', function () {
    $kp = fakeClient(new MockClient([RetrieveRate::class => err('validation_error', 'invalid_request', 422)]));

    try {
        $kp->rates->retrieve(CurrencyCode::USD, CurrencyCode::EUR);
        $this->fail('expected throw');
    } catch (ValidationException $e) {
        expect($e->errorCode)->toBe('invalid_request')
            ->and($e->param)->toBe('x')
            ->and($e->requestId)->toBe('req_1')
            ->and($e->statusCode)->toBe(422);
    }
});

it('maps 401 to AuthenticationException', function () {
    $kp = fakeClient(new MockClient([RetrieveRate::class => err('authentication_error', 'invalid_key', 401)]));

    expect(fn () => $kp->rates->retrieve(CurrencyCode::USD, CurrencyCode::EUR))
        ->toThrow(AuthenticationException::class);
});

it('maps 403 permission_error to PermissionException', function () {
    $kp = fakeClient(new MockClient([RetrieveRate::class => err('permission_error', 'insufficient_permissions', 403)]));

    expect(fn () => $kp->rates->retrieve(CurrencyCode::USD, CurrencyCode::EUR))
        ->toThrow(PermissionException::class);
});

it('maps 429 to RateLimitException and exposes Retry-After', function () {
    $kp = fakeClient(new MockClient([RetrieveRate::class => err('rate_limit_error', 'rate_limited', 429, ['Retry-After' => '7'])]));

    try {
        $kp->rates->retrieve(CurrencyCode::USD, CurrencyCode::EUR);
        $this->fail('expected throw');
    } catch (RateLimitException $e) {
        expect($e->retryAfter())->toBe(7);
    }
});

it('maps a payment_error code to its specific exception subclass', function () {
    $kp = fakeClient(new MockClient([
        CreateRefund::class => err('payment_error', 'partial_refund_unsupported', 422),
    ]));

    expect(fn () => $kp->refunds->create(new CreateRefundParams(payment: 'pi_1', amount: 500)))
        ->toThrow(PartialRefundUnsupportedException::class);
});

it('falls back gracefully for an unknown error code (forward-compat)', function () {
    $kp = fakeClient(new MockClient([RetrieveRate::class => err('validation_error', 'some_future_code', 422)]));

    // Unknown code, known type → still a ValidationException, never a crash.
    expect(fn () => $kp->rates->retrieve(CurrencyCode::USD, CurrencyCode::EUR))
        ->toThrow(ValidationException::class);
});
