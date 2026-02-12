<?php

namespace App\shared\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use App\shared\Exception\NotFoundException;
use App\shared\Repository\PaginatorInterface;

/**
 * @template T of object
 */
abstract class DoctrineEntityRepository extends ServiceEntityRepository
{
    protected string $entityAlias;

    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        ?string $entityAlias = null,
    ) {
        parent::__construct($registry, $entityClass);
        $this->entityAlias = $entityAlias ?? $this->toEntityAlias($entityClass);
    }

    /**
     * @param T $object
     */
    public function save(mixed $object, bool $flush = true): void
    {
        $this->getEntityManager()->persist($object);
        if (true === $flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param T $object
     */
    public function remove(mixed $object, bool $flush = true): void
    {
        $this->getEntityManager()->remove($object);
        if (true === $flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder($this->entityAlias);
    }

    /**
     * @return iterable<T>
     */
    public function all(): iterable
    {
        return $this->allQuery()->getQuery()->getResult();
    }

    /**
     * @return T
     */
    public function ofId(string $id, bool $strict = false): mixed
    {
        $entity = $this->allQuery()
            ->where($this->entityAlias.'.uuid = :uuid')
            ->setParameter('uuid', $id, UidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
        if (true === $strict && null === $entity) {
            throw new NotFoundException();
        }
        return $entity;
    }

    public function removeById(string $id): void
    {
        $this->createQueryBuilder($this->entityAlias)
            ->delete()
            ->where($this->entityAlias.'.uuid = :uuid')
            ->setParameter('uuid', $id, UidType::NAME)
            ->getQuery()
            ->execute();
    }

    protected function paginator(QueryBuilder $queryBuilder): PaginatorInterface
    {
        $paginator = new Paginator($queryBuilder->getQuery());

        return new DoctrinePaginator($paginator);
    }

    private function toEntityAlias(string $entityClass): string
    {
        $parts = explode('\\', $entityClass);

        return lcfirst(end($parts));
    }
}
