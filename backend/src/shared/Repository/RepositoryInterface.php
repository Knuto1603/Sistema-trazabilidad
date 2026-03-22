<?php

namespace App\shared\Repository;

use Countable;
use IteratorAggregate;

/**
 * @template T of object
 *
 * @implements IteratorAggregate<T>
 */
interface RepositoryInterface extends \IteratorAggregate, Countable
{
    /**
     * @return \Iterator<T>
     */
    public function getIterator(): \Iterator;

    public function count(): int;

    /**
     * @return PaginatorInterface<T>|null
     */
    public function paginator(): ?PaginatorInterface;

    /**
     * @return static<T>
     */
    public function withPagination(int $page, int $itemsPerPage): static;

    /**
     * @return static<T>
     */
    public function withoutPagination(): static;
}
