<?php

namespace App\apps\core\Service\Parametro\Filter;

use App\shared\Doctrine\UidType;
use App\shared\Service\Filter\FilterStrategy;
use Doctrine\ORM\QueryBuilder;


final readonly class ParentFilter implements FilterStrategy
{
    public function __construct(
        private ?string $parentId,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (null === $this->parentId) {
            return;
        }

        $queryBuilder
            ->andWhere('parent.uuid = :parentId')
            ->setParameter('parentId', $this->parentId, UidType::NAME);
    }
}
