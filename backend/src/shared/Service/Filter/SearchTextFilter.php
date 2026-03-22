<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;

final readonly class SearchTextFilter implements FilterStrategy
{
    public function __construct(
        private ?string $search,
        private array $fields,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        DoctrineSearchTextFilter::apply($queryBuilder, $this->search, $this->fields);
    }
}
