<?php

namespace App\shared\Service\Sorting;

use Doctrine\ORM\QueryBuilder;

interface SortingStrategy
{
    public function apply(QueryBuilder $queryBuilder): void;
}
