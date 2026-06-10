<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum BankMode: string
{
    case Test = 'test';
    case Live = 'live';

    public static function tryFromWire(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Test;
    }
}
