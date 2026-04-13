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

    public function findMaxNumeroPlantaByOperacion(int $operacionDbId, ?int $frutaDbId = null): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroPlanta)')
            ->join('d.operacion', 'o')
            ->where('o.id = :operacionId')
            ->setParameter('operacionId', $operacionDbId);

        if ($frutaDbId !== null) {
            $qb->join('d.fruta', 'fr')
               ->andWhere('fr.id = :frutaId')
               ->setParameter('frutaId', $frutaDbId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAdjacentByUuid(string $uuid): array
    {
        $current = $this->allQuery()
            ->andWhere('despacho.uuid = :uuid')
            ->setParameter('uuid', $uuid, \App\shared\Doctrine\UidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$current) {
            return ['prev' => null, 'next' => null];
        }

        $currentId = $current->getId();

        $prev = $this->createQueryBuilder('d')
            ->where('d.id < :id')
            ->setParameter('id', $currentId)
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $next = $this->createQueryBuilder('d')
            ->where('d.id > :id')
            ->setParameter('id', $currentId)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'prev' => $prev ? [
                'id'            => \App\shared\Doctrine\UidType::toString($prev->uuid()),
                'numeroPlanta'  => $prev->getNumeroPlanta(),
                'numeroCliente' => $prev->getNumeroCliente(),
            ] : null,
            'next' => $next ? [
                'id'            => \App\shared\Doctrine\UidType::toString($next->uuid()),
                'numeroPlanta'  => $next->getNumeroPlanta(),
                'numeroCliente' => $next->getNumeroCliente(),
            ] : null,
        ];
    }

    public function findMaxNumeroClienteByOperacion(int $clienteDbId, int $operacionDbId, ?int $frutaDbId = null): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('MAX(d.numeroCliente)')
            ->join('d.cliente', 'c')
            ->join('d.operacion', 'o')
            ->where('c.id = :clienteId')
            ->andWhere('o.id = :operacionId')
            ->setParameter('clienteId', $clienteDbId)
            ->setParameter('operacionId', $operacionDbId);

        if ($frutaDbId !== null) {
            $qb->join('d.fruta', 'fr')
               ->andWhere('fr.id = :frutaId')
               ->setParameter('frutaId', $frutaDbId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
