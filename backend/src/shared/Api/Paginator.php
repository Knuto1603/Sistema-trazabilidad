<?php

namespace App\shared\Api;

use App\shared\Repository\PaginatorInterface;

final class Paginator implements \IteratorAggregate
{
    public const PAGE = 0;
    public const ITEM_PER_PAGE = 10;

    /**
     * @param \Traversable<T> $items
     */
    public function __construct(
        private readonly \Traversable $items,
        private readonly int $currentPage,
        private readonly int $itemsPerPage,
        private readonly int $lastPage,
        private readonly int $totalItems,
    ) {
    }

    public static function create(
        array $items,
        int $currentPage,
        int $itemsPerPage,
        int $lastPage,
        int $totalItems,
    ): self {
        return new self(new \ArrayIterator($items), $currentPage, $itemsPerPage, $lastPage, $totalItems);
    }

    public static function ofModel(array $resources, PaginatorInterface $paginator): self
    {
        return new self(
            new \ArrayIterator($resources),
            $paginator->currentPage(),
            $paginator->itemsPerPage(),
            $paginator->lastPage(),
            $paginator->totalItems(),
        );
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        return $this->items;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage() > 0;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function previousPage(): int
    {
        return max(0, $this->currentPage());
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage() < $this->lastPage();
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function nextPage(): int
    {
        return min($this->lastPage(), $this->currentPage() + 1);
    }

    public function hasToPaginate(): bool
    {
        return $this->totalItems() > $this->itemsPerPage();
    }

    public function totalItems(): int
    {
        return $this->totalItems;
    }

    public function itemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function indexReversed(int $index): int
    {
        return $this->totalItems() - $this->index($index) + 1;
    }

    public function index(int $index): int
    {
        return $this->currentPage() * $this->itemsPerPage() + $index;
    }

    public function endIndex(): int
    {
        return ($this->startIndex() - 1) + $this->totalItems();
    }

    public function startIndex(): int
    {
        return $this->currentPage() * $this->itemsPerPage() + 1;
    }

    public function toArray(): array
    {
        return [
            'items' => iterator_to_array($this->getIterator()),
            'pagination' => [
                'length' => $this->totalItems(),
                'size' => $this->itemsPerPage(),
                'page' => $this->currentPage(),
                'lastPage' => $this->lastPage(),
                'startIndex' => $this->startIndex(),
                'endIndex' => $this->endIndex(),
            ],
        ];
    }
}
