<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\FrutaVariedad;
use App\shared\Doctrine\DoctrineEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<FrutaVariedad>
 */
class FrutaVariedadRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrutaVariedad::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('fv')
            ->select(['fv', 'fruta'])
            ->leftJoin('fv.fruta', 'fruta');
    }

    public function byFruta(int $frutaId): array
    {
        return $this->allQuery()
            ->where('fruta.id = :frutaId')
            ->setParameter('frutaId', $frutaId)
            ->orderBy('fv.nombre', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function byFrutaActive(int $frutaId): array
    {
        return $this->allQuery()
            ->where('fruta.id = :frutaId')
            ->andWhere('fv.isActive = true')
            ->setParameter('frutaId', $frutaId)
            ->orderBy('fv.nombre', 'asc')
            ->getQuery()
            ->getResult();
    }
}
