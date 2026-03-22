<?php

namespace App\apps\core\Repository;

use App\shared\Doctrine\UidType;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use App\apps\core\Entity\Parametro;
use App\shared\Doctrine\DoctrineEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Parametro>
 *
 * @method Parametro|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parametro|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parametro[]    findAll()
 * @method Parametro[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParametroRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parametro::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('parametro')
            ->select(['parametro', 'parent'])
            ->leftJoin('parametro.parent', 'parent');
    }

    public function allParents(): array
    {
        return $this->allQuery()
            ->select('parametro.uuid as id')
            ->addSelect('parametro.name as name')
            ->where('parametro.isActive = true')
            ->andWhere('parametro.parent is null')
            ->orderBy('parametro.name', 'asc')
            ->getQuery()
            ->getResult();
    }

    /** @return Parametro[] */
    public function findByParentAlias(string $parentAlias): array
    {
        return $this->allQuery()
            ->andWhere('parent.alias = :parentAlias')
            ->setParameter('parentAlias', $parentAlias)
            ->getQuery()
            ->getResult();
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function downloadAndFilter(FilterService $filterService): iterable
    {
        $queryBuilder = $this->allQuery()
            ->select('parent.name as parentName')
            ->addSelect('parametro.name as parametroName')
            ->addSelect('parametro.alias as parametroAlias')
            ->addSelect('parametro.isActive as parametroIsActive');

        $filterService->apply($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByParentAliasAndUUID(string $parentAlias, string $uuid): ?Parametro
    {
        return $this->allQuery()
            ->andWhere('parent.alias = :parentAlias')
            ->andWhere('parametro.uuid = :uuid')
            ->setParameter('parentAlias', $parentAlias)
            ->setParameter('uuid', $uuid, UidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function allShared(): array
    {
        return $this->createQueryBuilder('parametro')
            ->select('parametro.uuid as id')
            ->addSelect('parametro.name as name')
            ->addSelect('parametro.name as alias')
            ->addSelect('parent.alias as parentAlias')
            ->join('parametro.parent', 'parent')
            ->where('parametro.isActive = true')
            ->orderBy('parametro.name', 'asc')
            ->getQuery()
            ->getResult();
    }
}
