<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use App\Shared\Service\Dto\PaginationDto;

final class DoctrinePaginationFilter
{
    public static function apply(QueryBuilder $queryBuilder, PaginationDto $dto): void
    {
        $queryBuilder
            ->setFirstResult($dto->page * $dto->itemsPerPage)
            ->setMaxResults($dto->itemsPerPage);
    }
}