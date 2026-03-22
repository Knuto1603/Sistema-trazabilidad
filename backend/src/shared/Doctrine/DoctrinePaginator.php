<?php

namespace App\shared\Doctrine;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Shared\Repository\PaginatorInterface;

/**
 * @template T of object
 *
 * @implements PaginatorInterface<T>
 */
final readonly class DoctrinePaginator implements PaginatorInterface
{
    private int $firstResult;
    private int $maxResults;
    private int $count;
    private int $totalItems;

    /**
     * @param Paginator<T> $paginator
     */
    public function __construct(
        private Paginator $paginator,
    ) {
        $firstResult = $paginator->getQuery()->getFirstResult();
        if (null === $firstResult) {
            throw new \InvalidArgumentException('Missing firstResult from the query.');
        }

        $maxResults = $paginator->getQuery()->getMaxResults();
        if (null === $maxResults) {
            throw new \InvalidArgumentException('Missing maxResults from the query.');
        }

        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
        $this->count = iterator_count($this->getIterator());
        $this->totalItems = \count($this->paginator);
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        return $this->paginator->getIterator();
    }

    public function itemsPerPage(): int
    {
        return $this->maxResults;
    }

    public function currentPage(): int
    {
        if (0 > $this->maxResults) {
            return 0;
        }

        return (int) floor($this->firstResult / $this->maxResults);
    }

    public function lastPage(): int
    {
        if (0 > $this->maxResults) {
            return 0;
        }

        return (int) (ceil($this->totalItems() / $this->maxResults) ?: 1);
    }

    public function totalItems(): int
    {
        return $this->totalItems;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function endIndex(): int
    {
        return ($this->startIndex() - 1) + $this->count();
    }

    public function startIndex(): int
    {
        return $this->currentPage() * $this->itemsPerPage() + 1;
    }

    public function pagination(): array
    {
        return [
            'page' => $this->currentPage(),
            'itemsPerPage' => $this->itemsPerPage(),
            'count' => $this->count(),
            'totalItems' => $this->totalItems(),
            'startIndex' => $this->startIndex(),
            'endIndex' => $this->endIndex(),
        ];
    }
}
