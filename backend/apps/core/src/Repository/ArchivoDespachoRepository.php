<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\ArchivoDespacho;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Doctrine\UidType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<ArchivoDespacho>
 */
class ArchivoDespachoRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchivoDespacho::class);
    }

    public function findByDespachoUuid(string $despachoUuid): array
    {
        return $this->createQueryBuilder('a')
            ->select(['a', 'd'])
            ->leftJoin('a.despacho', 'd')
            ->where('d.uuid = :uuid')
            ->setParameter('uuid', $despachoUuid, UidType::NAME)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
