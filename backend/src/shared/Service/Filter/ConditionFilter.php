<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;

final readonly class ConditionFilter implements FilterStrategy
{
    public function __construct(
        private string $condition,
        private array $parameters = [],
    ) {
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->andWhere($this->condition);
        foreach ($this->parameters as $key => $value) {
            $queryBuilder->setParameter($key, $value);
        }
    }
}
