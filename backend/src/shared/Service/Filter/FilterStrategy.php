<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;

interface FilterStrategy
{
    public function apply(QueryBuilder $queryBuilder): void;
}