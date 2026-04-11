<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Factura;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Doctrine\UidType;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Factura>
 */
class FacturaRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Factura::class);
    }

    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('factura')
            ->select(['factura', 'despacho', 'cliente'])
            ->leftJoin('factura.despacho', 'despacho')
            ->leftJoin('despacho.cliente', 'cliente')
            ->orderBy('factura.fechaEmision', 'DESC');
    }

    public function createQueryBuilderWithPagos(): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->select(['f', 'd', 'c', 'o', 'pagos', 'v'])
            ->leftJoin('f.despacho', 'd')
            ->leftJoin('d.cliente', 'c')
            ->leftJoin('d.operacion', 'o')
            ->leftJoin('f.pagos', 'pagos')
            ->leftJoin('pagos.voucher', 'v')
            ->orderBy('f.fechaEmision', 'DESC');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function findByDespachoUuid(string $despachoUuid): array
    {
        return $this->createQueryBuilder('f')
            ->select(['f', 'd', 'c'])
            ->leftJoin('f.despacho', 'd')
            ->leftJoin('d.cliente', 'c')
            ->where('d.uuid = :uuid')
            ->setParameter('uuid', $despachoUuid, UidType::NAME)
            ->orderBy('f.fechaEmision', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForReporte(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select(['f', 'd', 'c', 'fr'])
            ->leftJoin('f.despacho', 'd')
            ->leftJoin('d.cliente', 'c')
            ->leftJoin('d.fruta', 'fr')
            ->where('f.isActive = true')
            ->orderBy('f.serie', 'ASC')
            ->addOrderBy('f.correlativo', 'ASC');

        if ($search) {
            $qb->andWhere('c.razonSocial LIKE :s OR f.numeroDocumento LIKE :s OR f.contenedor LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function deleteByDespacho(object $despacho): void
    {
        $this->createQueryBuilder('f')
            ->delete()
            ->where('f.despacho = :despacho')
            ->setParameter('despacho', $despacho)
            ->getQuery()
            ->execute();
    }
}
