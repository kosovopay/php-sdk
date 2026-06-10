<?php

declare(strict_types=1);

namespace KosovoPay\Http;

use KosovoPay\Config;
use KosovoPay\Exceptions\ErrorMapper;
use KosovoPay\Internal\Cast;
use KosovoPay\KosovoPay;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector as SaloonConnector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\Paginator;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

/**
 * The shared HTTP context: base URL, bearer auth, version + user-agent headers,
 * timeouts, retry policy, typed-error conversion, and cursor pagination.
 */
final class Connector extends SaloonConnector implements HasPagination
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function __construct(private readonly Config $kpConfig)
    {
        $this->tries = max(1, $kpConfig->maxRetries);
        $this->retryInterval = $kpConfig->retryIntervalMs;
        $this->useExponentialBackoff = true;
    }

    public function resolveBaseUrl(): string
    {
        return rtrim($this->kpConfig->baseUrl, '/');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->kpConfig->apiKey);
    }

    /** @return array<string, string> */
    protected function defaultHeaders(): array
    {
        return [
            'Kosovopay-Version' => $this->kpConfig->apiVersion,
            'User-Agent' => 'kosovopay-php/'.KosovoPay::VERSION.' (php/'.PHP_VERSION.')',
        ];
    }

    /** @return array<string, mixed> */
    protected function defaultConfig(): array
    {
        return [
            'connect_timeout' => $this->kpConfig->connectTimeout,
            'timeout' => $this->kpConfig->requestTimeout,
        ];
    }

    /**
     * Retry network failures, 429, and 5xx. Never retry deterministic 4xx, and
     * never retry a mutating 5xx unless an Idempotency-Key is attached (so a
     * blind retry can't double-charge).
     */
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($exception instanceof FatalRequestException) {
            return true;
        }

        $status = $exception->getResponse()->status();

        if ($status === 429) {
            return true;
        }

        if ($status >= 500) {
            $isSafe = in_array($request->getMethod(), [Method::GET, Method::HEAD], true);

            return $isSafe || $request->headers()->get('Idempotency-Key') !== null;
        }

        return false;
    }

    /** Convert the server error envelope into a typed exception. */
    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        $retryAfter = $response->header('Retry-After');

        return ErrorMapper::make(
            body: Cast::object($response->json()),
            status: $response->status(),
            retryAfter: is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }

    public function paginate(Request $request): Paginator
    {
        return new CursorPaginator($this, $request);
    }
}
