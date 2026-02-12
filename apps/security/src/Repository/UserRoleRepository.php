<?php

namespace App\apps\security\Repository;


use App\apps\security\Entity\UserRole;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<UserRole>
 *
 * @method UserRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRole[]    findAll()
 * @method UserRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRoleRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    /** @return UserRole[] */
    public function allFilter(array $params): iterable
    {
        $queryBuilder = $this->allQuery();
        $this->filters($queryBuilder, $params);

        return $queryBuilder->getQuery()->getResult();
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('userRole')
            ->select(['userRole']);
    }

    public function allShared()
    {
        return $this->allQuery()
            ->select('userRole.uuid AS id')
            ->addSelect('userRole.name AS name')
            ->where('userRole.isActive = true')
            ->orderBy('userRole.name', 'asc')
            ->getQuery()
            ->getResult();
    }


    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    private function filters(QueryBuilder $queryBuilder, array $params): void
    {
        $this->textFilter($queryBuilder, $params, [
            $this->entityAlias.'.name',
            $this->entityAlias.'.alias',
        ]);

        [$sort, $order] = $this->orderValues($params);
        if (null !== $sort && null !== $order) {
            $this->orderFilter($queryBuilder, [
                $this->entityAlias.'.'.$sort => $order,
            ]);
        }
    }

    public function ofUser(string $userUuid): iterable
    {
        return $this->createQueryBuilder($this->entityAlias)
            ->innerJoin($this->entityAlias.'.users', 'user')
            ->where('user.uuid = :userUuid')
            ->setParameter('userUuid', $userUuid)
            ->getQuery()
            ->getResult();
    }

    /** @return UserRole[] */
    public function allNames(): iterable
    {
        return $this->allQuery()
            ->select($this->entityAlias.'.name as name')
            ->addSelect($this->entityAlias.'.alias as alias')
            ->where($this->entityAlias.'.isActive = true')
            ->getQuery()
            ->getResult();
    }
}
