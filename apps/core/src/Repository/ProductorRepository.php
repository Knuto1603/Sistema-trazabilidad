<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Productor;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Productor>
 */
class ProductorRepository extends DoctrineEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Productor::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('productor')
            ->select(['productor']);
    }

    /**
     * Query con información de campañas (a través de ProductorCampahna)
     */
    public function allQueryWithCampahnas(): QueryBuilder
    {
        return $this->createQueryBuilder('productor')
            ->select(['productor', 'productorCampahnas', 'campahna', 'fruta'])
            ->leftJoin('productor.campahnas', 'productorCampahnas')
            ->leftJoin('productorCampahnas.campahna', 'campahna')
            ->leftJoin('campahna.fruta', 'fruta');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function downloadAndFilter(FilterService $filterService): iterable
    {
        $queryBuilder = $this->allQueryWithCampahnas()
            ->select('productor.codigo as codigo')
            ->addSelect('productor.nombre as nombre')
            ->addSelect('productor.clp as clp')
            ->addSelect('productor.mtdCeratitis as mtdCeratitis')
            ->addSelect('productor.mtdAnastrepha as mtdAnastrepha')
            ->addSelect('productor.isActive as isActive')
            ->addSelect('productor.createdAt as createdAt');

        $filterService->apply($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }

    public function allShared(): array
    {
        return $this->allQuery()
            ->select('productor.uuid as id')
            ->addSelect('productor.nombre as nombre')
            ->addSelect('CONCAT(productor.codigo, \' - \', productor.nombre) as nombreCompleto')
            ->where('productor.isActive = true')
            ->orderBy('productor.nombre', 'asc')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra el último código de productor
     */
    public function findLastProducerCode(): ?string
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.codigo')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['codigo'] : null;
    }

    /**
     * Buscar productores que NO están en una campaña específica
     */
    public function findNotInCampahna(string $campahnaId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.id NOT IN (
                SELECT IDENTITY(pc.productor)
                FROM App\apps\core\Entity\ProductorCampahna pc
                JOIN pc.campahna c
                WHERE c.uuid = :campahnaId
            )')
            ->andWhere('p.isActive = true')
            ->setParameter('campahnaId', $campahnaId)
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
