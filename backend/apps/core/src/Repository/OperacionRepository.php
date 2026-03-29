<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Operacion;
use App\shared\Doctrine\DoctrineEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Operacion>
 */
class OperacionRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operacion::class);
    }

    public function findBySede(string $sede): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.sede = :sede')
            ->andWhere('o.isActive = true')
            ->setParameter('sede', $sede)
            ->orderBy('o.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isActive = true')
            ->orderBy('o.sede', 'ASC')
            ->addOrderBy('o.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
