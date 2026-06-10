<?php

declare(strict_types=1);

namespace KosovoPay\Exceptions;

final class RateLimitException extends KosovoPayException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        ?string $errorCode = null,
        ?string $errorType = null,
        ?string $param = null,
        ?string $requestId = null,
        ?string $docUrl = null,
        int $statusCode = 429,
    ) {
        parent::__construct($message, $errorCode, $errorType, $param, $requestId, $docUrl, $statusCode);
    }

    /** Seconds the caller should wait before retrying, if the server told us. */
    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
