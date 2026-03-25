<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Cliente;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Cliente>
 */
class ClienteRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('cliente')
            ->select(['cliente']);
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function findByRuc(string $ruc): ?Cliente
    {
        return $this->findOneBy(['ruc' => $ruc]);
    }
}
