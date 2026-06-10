<?php

declare(strict_types=1);

namespace KosovoPay\Http;

use Generator;
use KosovoPay\Internal\Cast;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;

/**
 * Cursor pagination for the KosovoPay list envelope:
 * `{ object: "list", data: [...], has_more: bool, url }`.
 *
 * Pages forward with `starting_after = <last item id>` until `has_more` is false.
 * {@see iterateRows()} streams the raw row maps across pages; resources map each
 * row to a typed DTO.
 */
final class CursorPaginator extends Paginator
{
    protected function isLastPage(Response $response): bool
    {
        return $response->json('has_more') === false;
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    protected function getPageItems(Response $response, Request $request): array
    {
        return Cast::rows(Cast::object($response->json()), 'data');
    }

    protected function applyPagination(Request $request): Request
    {
        if ($this->currentResponse instanceof Response) {
            $data = Cast::rows(Cast::object($this->currentResponse->json()), 'data');
            $last = end($data);

            if (is_array($last) && isset($last['id'])) {
                $request->query()->add('starting_after', Cast::string($last['id']));
            }
        }

        if (isset($this->perPageLimit)) {
            $request->query()->add('limit', $this->perPageLimit);
        }

        return $request;
    }

    /**
     * Stream every row across all pages.
     *
     * @return Generator<int, array<array-key, mixed>>
     */
    public function iterateRows(): Generator
    {
        foreach ($this as $page) {
            if (! $page instanceof Response) {
                continue;
            }

            foreach (Cast::rows(Cast::object($page->json()), 'data') as $row) {
                yield $row;
            }
        }
    }
}
