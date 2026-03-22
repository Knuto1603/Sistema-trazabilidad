<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Campahna;
use App\apps\core\Entity\Productor;
use App\apps\core\Entity\ProductorCampahna;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<ProductorCampahna>
 */
class ProductorCampahnaRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductorCampahna::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('productorCampahna')
            ->select(['productorCampahna', 'productor', 'campahna', 'fruta'])
            ->join('productorCampahna.productor', 'productor')
            ->join('productorCampahna.campahna', 'campahna')
            ->leftJoin('campahna.fruta', 'fruta');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    /**
     * Encuentra todos los productores de una campaña específica
     * @return ProductorCampahna[]
     */
    public function findByCampahna(Campahna $campahna): array
    {
        return $this->findBy(['campahna' => $campahna, 'isActive' => true]);
    }

    /**
     * Encuentra todas las campañas de un productor específico
     * @return ProductorCampahna[]
     */
    public function findByProductor(Productor $productor): array
    {
        return $this->findBy(['productor' => $productor, 'isActive' => true]);
    }

    /**
     * Verifica si un productor ya está asociado a una campaña
     */
    public function existsProductorInCampahna(Productor $productor, Campahna $campahna): bool
    {
        return $this->findOneBy(['productor' => $productor, 'campahna' => $campahna]) !== null;
    }

    /**
     * Encuentra la relación específica entre un productor y una campaña
     */
    public function findByProductorAndCampahna(Productor $productor, Campahna $campahna): ?ProductorCampahna
    {
        return $this->findOneBy(['productor' => $productor, 'campahna' => $campahna]);
    }

    /**
     * Encuentra productores activos en una campaña por UUID
     */
    public function findActivesByCampahnaId(string $campahnaId): array
    {
        return $this->allQuery()
            ->where('campahna.uuid = :campahnaId')
            ->andWhere('productorCampahna.activo = true')
            ->andWhere('productor.isActive = true')
            ->setParameter('campahnaId', $campahnaId)
            ->getQuery()
            ->getResult();
    }
}
