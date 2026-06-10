<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum BankCode: string
{
    case Procredit = 'procredit';
    case Procard = 'procard';
    case Onefor = 'onefor';

    /** Forward-compatible fallback for an unrecognised wire value. */
    case Unknown = 'unknown';

    public static function tryFromWire(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Unknown;
    }
}
