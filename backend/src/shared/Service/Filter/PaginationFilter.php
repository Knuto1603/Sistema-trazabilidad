<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use App\shared\Service\Dto\PaginationDto;

final readonly class PaginationFilter implements FilterStrategy
{
    public function __construct(
        private ?int $page,
        private ?int $itemsPerPage
    )  {
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        DoctrinePaginationFilter::apply($queryBuilder, PaginationDto::create($this->page, $this->itemsPerPage));
    }
}
