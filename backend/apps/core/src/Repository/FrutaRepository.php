<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Fruta;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Fruta>
 */
class FrutaRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fruta::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('fruta')
            ->select(['fruta']);
    }

    public function allShared(): array
    {
        return $this->allQuery()
            ->select('fruta.uuid as id')
            ->addSelect('fruta.nombre as nombre')
            ->addSelect('fruta.codigo as codigo')
            ->where('fruta.isActive = true')
            ->getQuery()
            ->getResult();
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array{
        return $this->createQueryBuilder('fruta')
            ->select(['fruta'])
            ->where('fruta.isActive = true')
            ->orderBy('fruta.nombre', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function downloadAndFilter(FilterService $filterService): iterable
    {
        $queryBuilder = $this->allQuery()
            ->addSelect('fruta.nombre as frutaName')
            ->addSelect('fruta.codigo as frutaCodigo')
            ->addSelect('fruta.createdAt as createdAt')
            ->addSelect('fruta.updatedAt as updateAt')
            ->addSelect('fruta.isActive as frutaIsActive');

        $filterService->apply($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }
/*
    public function allShared(): array
    {
        return $this->createQueryBuilder('fruta')
            ->select('fruta.uuid as id')
            ->addSelect('fruta.nombre as name')
            ->addSelect('fruta.codigo as codigo')
            ->where('fruta.isActive = true')
            ->orderBy('fruta.nombre', 'asc')
            ->getQuery()
            ->getResult();
    }
*/
}
