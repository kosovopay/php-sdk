<?php

declare(strict_types=1);

namespace KosovoPay\Exceptions;

use Exception;

/**
 * Base of every error this SDK throws. Carries the full server error envelope
 * so callers can branch on a stable machine code, surface the doc URL, and quote
 * the request id in support tickets.
 */
abstract class KosovoPayException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorType = null,
        public readonly ?string $param = null,
        public readonly ?string $requestId = null,
        public readonly ?string $docUrl = null,
        public readonly int $statusCode = 0,
    ) {
        parent::__construct($message);
    }
}
