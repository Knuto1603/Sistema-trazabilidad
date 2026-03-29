<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Despacho;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Despacho>
 */
class DespachoRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Despacho::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('despacho')
            ->select(['despacho', 'cliente', 'fruta', 'operacion'])
            ->leftJoin('despacho.cliente', 'cliente')
            ->leftJoin('despacho.fruta', 'fruta')
            ->leftJoin('despacho.operacion', 'operacion')
            ->orderBy('despacho.id', 'DESC');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function findMaxNumeroCliente(int $clienteDbId): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroCliente)')
            ->join('d.cliente', 'c')
            ->where('c.id = :clienteId')
            ->setParameter('clienteId', $clienteDbId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findMaxNumeroPlanta(): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroPlanta)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findMaxNumeroPlantaByOperacion(int $operacionDbId): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroPlanta)')
            ->join('d.operacion', 'o')
            ->where('o.id = :operacionId')
            ->setParameter('operacionId', $operacionDbId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findMaxNumeroClienteByOperacion(int $clienteDbId, int $operacionDbId): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroCliente)')
            ->join('d.cliente', 'c')
            ->join('d.operacion', 'o')
            ->where('c.id = :clienteId')
            ->andWhere('o.id = :operacionId')
            ->setParameter('clienteId', $clienteDbId)
            ->setParameter('operacionId', $operacionDbId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
