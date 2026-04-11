<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\PagoFactura;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Doctrine\UidType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<PagoFactura>
 */
class PagoFacturaRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagoFactura::class);
    }

    /** Todos los pagos de una factura (activos e inactivos) */
    public function findByFacturaUuid(string $facturaUuid): array
    {
        return $this->createQueryBuilder('p')
            ->select(['p', 'v', 'c'])
            ->leftJoin('p.voucher', 'v')
            ->leftJoin('v.cliente', 'c')
            ->leftJoin('p.factura', 'f')
            ->where('f.uuid = :uuid')
            ->setParameter('uuid', $facturaUuid, UidType::NAME)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Suma de montos aplicados activos para una factura (int id) */
    public function sumActivosByFacturaId(int $facturaId): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montoAplicado) as total')
            ->where('p.factura = :facturaId')
            ->andWhere('p.isActive = true')
            ->setParameter('facturaId', $facturaId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}
