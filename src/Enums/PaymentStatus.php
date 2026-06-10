<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Canceled = 'canceled';

    /** Forward-compatible fallback for an unrecognised wire value. */
    case Unknown = 'unknown';

    public static function tryFromWire(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Unknown;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Captured, self::Refunded, self::Failed, self::Canceled], true);
    }
}
