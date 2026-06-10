<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum WebhookEventType: string
{
    case PaymentCreated = 'payment.created';
    case PaymentCaptured = 'payment.captured';
    case PaymentFailed = 'payment.failed';
    case PaymentCanceled = 'payment.canceled';
    case PaymentExpired = 'payment.expired';
    case RefundSucceeded = 'refund.succeeded';
    case RefundFailed = 'refund.failed';

    /** Forward-compatible fallback for an unrecognised wire value. */
    case Unknown = 'unknown';

    public static function tryFromWire(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Unknown;
    }

    public function isRefund(): bool
    {
        return $this === self::RefundSucceeded || $this === self::RefundFailed;
    }
}
