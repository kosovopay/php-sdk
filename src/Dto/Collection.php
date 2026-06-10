<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use KosovoPay\Http\CursorPaginator;
use KosovoPay\Internal\Cast;
use Traversable;

/**
 * A single-page list envelope: `{ object: "list", data, has_more, url }`.
 *
 * Used for the non-paginated lists (banks, currencies, webhook endpoints,
 * timeline). For paginated resources (payments, refunds) the resource returns a
 * {@see CursorPaginator} instead, which streams across pages.
 *
 * @template TDto
 *
 * @implements IteratorAggregate<int, TDto>
 */
final readonly class Collection implements Countable, IteratorAggregate
{
    /**
     * @param  list<TDto>  $data
     */
    public function __construct(
        public array $data,
        public bool $hasMore,
        public string $url,
    ) {}

    /**
     * @template TItem
     *
     * @param  array<array-key, mixed>  $body
     * @param  callable(array<array-key, mixed>): TItem  $mapper
     * @return self<TItem>
     */
    public static function fromArray(array $body, callable $mapper): self
    {
        return new self(
            data: array_values(array_map($mapper, Cast::rows($body, 'data'))),
            hasMore: Cast::bool($body['has_more'] ?? null),
            url: Cast::string($body['url'] ?? null),
        );
    }

    public function count(): int
    {
        return count($this->data);
    }

    /** @return list<TDto> */
    public function all(): array
    {
        return $this->data;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
