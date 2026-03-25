<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\TipoCambio;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<TipoCambio>
 */
class TipoCambioRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoCambio::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('tipoCambio')
            ->select(['tipoCambio'])
            ->orderBy('tipoCambio.fecha', 'DESC');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function findByFecha(\DateTimeInterface $fecha): ?TipoCambio
    {
        return $this->createQueryBuilder('tc')
            ->where('tc.fecha = :fecha')
            ->setParameter('fecha', $fecha->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
