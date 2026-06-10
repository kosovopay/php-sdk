<?php

declare(strict_types=1);

namespace KosovoPay\Exceptions;

use KosovoPay\Exceptions\Payment\AmountBelowMinimumException;
use KosovoPay\Exceptions\Payment\AmountStepInvalidException;
use KosovoPay\Exceptions\Payment\BankNotEnabledException;
use KosovoPay\Exceptions\Payment\BankUnreachableException;
use KosovoPay\Exceptions\Payment\PartialRefundUnsupportedException;
use KosovoPay\Exceptions\Payment\PaymentNotCancelableException;
use KosovoPay\Exceptions\Payment\PaymentNotRefundableException;
use KosovoPay\Exceptions\Payment\RefundExceedsRemainingException;
use KosovoPay\Internal\Cast;

/**
 * Maps a server error envelope to the matching typed exception.
 *
 * Resolution order: exact `code` → 429/rate-limit → `type` family → ApiException.
 * An unrecognised code never crashes — it falls back to its type family, so a
 * server that adds a new code stays usable by an old SDK.
 */
final class ErrorMapper
{
    /** @var array<string, class-string<KosovoPayException>> */
    private const BY_CODE = [
        'amount_below_minimum' => AmountBelowMinimumException::class,
        'amount_step_invalid' => AmountStepInvalidException::class,
        'bank_not_enabled' => BankNotEnabledException::class,
        'bank_unreachable' => BankUnreachableException::class,
        'payment_not_cancelable' => PaymentNotCancelableException::class,
        'payment_not_refundable' => PaymentNotRefundableException::class,
        'refund_exceeds_remaining' => RefundExceedsRemainingException::class,
        'partial_refund_unsupported' => PartialRefundUnsupportedException::class,
    ];

    /** @var array<string, class-string<KosovoPayException>> */
    private const BY_TYPE = [
        'authentication_error' => AuthenticationException::class,
        'permission_error' => PermissionException::class,
        'validation_error' => ValidationException::class,
        'idempotency_error' => IdempotencyException::class,
        'payment_error' => PaymentException::class,
        'api_error' => ApiException::class,
    ];

    /**
     * @param  array<array-key, mixed>  $body  decoded response body
     */
    public static function make(array $body, int $status, ?int $retryAfter = null): KosovoPayException
    {
        $error = Cast::map($body, 'error');

        $message = Cast::string($error['message'] ?? null, 'The request failed.');
        $code = Cast::nullableString($error['code'] ?? null);
        $type = Cast::nullableString($error['type'] ?? null);
        $param = Cast::nullableString($error['param'] ?? null);
        $requestId = Cast::nullableString($error['request_id'] ?? null);
        $docUrl = Cast::nullableString($error['doc_url'] ?? null);

        if ($type === 'rate_limit_error' || $status === 429) {
            return new RateLimitException($message, $retryAfter, $code, $type, $param, $requestId, $docUrl, $status);
        }

        $class = ($code !== null ? (self::BY_CODE[$code] ?? null) : null)
            ?? ($type !== null ? (self::BY_TYPE[$type] ?? null) : null)
            ?? ApiException::class;

        return new $class($message, $code, $type, $param, $requestId, $docUrl, $status);
    }
}
