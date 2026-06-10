<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum CheckoutMode: string
{
    case Hosted = 'hosted';
    case Direct = 'direct';

    public function requiresBankCode(): bool
    {
        return $this === self::Direct;
    }
}
