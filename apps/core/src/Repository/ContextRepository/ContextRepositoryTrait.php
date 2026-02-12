<?php

namespace App\apps\core\Repository\ContextRepository;

use App\apps\core\Entity\Campahna;
use Doctrine\ORM\QueryBuilder;

trait ContextRepositoryTrait
{
    /**
     * Aplica el contexto de campaña al QueryBuilder
     */
    protected function aplicarContexto(
        QueryBuilder $qb,
        string $alias,
        ?Campahna $campahna = null
    ): void
    {
        if ($campahna) {
            $qb->andWhere("{$alias}.campahna = :campahna")
                ->setParameter('campahna', $campahna);
        }
    }

    /**
     * Crear QueryBuilder con contexto de campaña aplicado
     */
    protected function createQueryBuilderWithContext(string $alias, ?Campahna $campahna = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder($alias);
        $this->aplicarContexto($qb, $alias, $campahna);
        return $qb;
    }

    /**
     * Obtener todos los registros filtrados por campaña
     */
    public function findByCampahna(Campahna $campahna, array $criteria = []): array
    {
        $qb = $this->createQueryBuilderWithContext('e', $campahna);

        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :{$field}")
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getResult();
    }
}
