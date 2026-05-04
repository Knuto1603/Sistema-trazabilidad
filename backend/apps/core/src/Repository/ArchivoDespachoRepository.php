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

    public function deleteByDespacho(object $despacho): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.despacho = :despacho')
            ->setParameter('despacho', $despacho)
            ->getQuery()
            ->execute();
    }

    public function findByFacturaUuid(string $facturaUuid): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.factura', 'f')
            ->where('f.uuid = :uuid')
            ->setParameter('uuid', $facturaUuid, UidType::NAME)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFactura(object $factura): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.factura = :factura')
            ->setParameter('factura', $factura)
            ->getQuery()
            ->getResult();
    }

    public function findXmlWithFacturaByDespachoAndBaseName(object $despacho, string $baseName): ?ArchivoDespacho
    {
        return $this->createQueryBuilder('a')
            ->where('a.despacho = :despacho')
            ->andWhere('a.tipoArchivo IN (:tipos)')
            ->andWhere('a.nombre LIKE :pattern')
            ->andWhere('a.factura IS NOT NULL')
            ->setParameter('despacho', $despacho)
            ->setParameter('tipos', ['FACTURA_XML', 'GUIA_XML'])
            ->setParameter('pattern', '%\_' . $baseName . '.%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
