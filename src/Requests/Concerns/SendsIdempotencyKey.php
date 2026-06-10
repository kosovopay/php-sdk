<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Concerns;

use Symfony\Component\Uid\Ulid;

/**
 * Attaches an Idempotency-Key to a mutating request. A caller-supplied key wins;
 * otherwise a ULID is generated once and reused across retries, so a retried
 * create can never double-charge.
 */
trait SendsIdempotencyKey
{
    protected ?string $idempotencyKey = null;

    /** @return array<string, string> */
    public function defaultHeaders(): array
    {
        return ['Idempotency-Key' => $this->idempotencyKey ??= (string) new Ulid];
    }
}
