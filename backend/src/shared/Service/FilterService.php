<?php

namespace App\shared\Service;

use Doctrine\ORM\QueryBuilder;
use App\shared\Service\Filter\FilterStrategy;
use App\shared\Service\Sorting\SortingStrategy;

class FilterService
{
    /** @var FilterStrategy[] */
    private array $filters = [];

    /** @var SortingStrategy[] */
    private array $sortings = [];

    public function addFilter(FilterStrategy $filter): void
    {
        $this->filters[] = $filter;
    }

    public function addSorting(SortingStrategy $sorting): void
    {
        $this->sortings[] = $sorting;
    }

    public function applyFilters(QueryBuilder $queryBuilder): void
    {
        foreach ($this->filters as $filter) {
            $filter->apply($queryBuilder);
        }
    }

    public function applySortings(QueryBuilder $queryBuilder): void
    {
        foreach ($this->sortings as $sorting) {
            $sorting->apply($queryBuilder);
        }
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        $this->applyFilters($queryBuilder);
        $this->applySortings($queryBuilder);
    }
}