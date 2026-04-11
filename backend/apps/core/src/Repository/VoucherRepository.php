<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Voucher;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Doctrine\UidType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Voucher>
 */
class VoucherRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voucher::class);
    }

    public function findByNumeroAndCliente(string $numero, int $clienteId): ?Voucher
    {
        return $this->createQueryBuilder('v')
            ->select(['v', 'c', 'pagos'])
            ->leftJoin('v.cliente', 'c')
            ->leftJoin('v.pagos', 'pagos')
            ->where('v.numero = :numero')
            ->andWhere('c.id = :clienteId')
            ->andWhere('v.isActive = true')
            ->setParameter('numero', $numero)
            ->setParameter('clienteId', $clienteId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Búsqueda para autocomplete: por número parcial y clienteId, solo con saldo disponible */
    public function searchDisponibles(string $clienteUuid, string $q = '', int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select(['v', 'c', 'pagos'])
            ->leftJoin('v.cliente', 'c')
            ->leftJoin('v.pagos', 'pagos')
            ->where('c.uuid = :clienteUuid')
            ->andWhere('v.isActive = true')
            ->setParameter('clienteUuid', $clienteUuid, UidType::NAME)
            ->orderBy('v.fecha', 'DESC')
            ->setMaxResults($limit);

        if ($q !== '') {
            $qb->andWhere('v.numero LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithPagos(string $uuid): ?Voucher
    {
        return $this->createQueryBuilder('v')
            ->select(['v', 'c', 'pagos', 'pf'])
            ->leftJoin('v.cliente', 'c')
            ->leftJoin('v.pagos', 'pagos')
            ->leftJoin('pagos.factura', 'pf')
            ->where('v.uuid = :uuid')
            ->setParameter('uuid', $uuid, UidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
