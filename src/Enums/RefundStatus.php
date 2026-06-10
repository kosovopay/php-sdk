<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /** Forward-compatible fallback for an unrecognised wire value. */
    case Unknown = 'unknown';

    public static function tryFromWire(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Unknown;
    }
}
