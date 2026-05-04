<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\Factura;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Doctrine\UidType;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
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

    public function findAllForReporte(?string $search = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array
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

        if ($fechaDesde) {
            $qb->andWhere('f.fechaEmision >= :fechaDesde')
               ->setParameter('fechaDesde', new \DateTime($fechaDesde));
        }

        if ($fechaHasta) {
            $qb->andWhere('f.fechaEmision <= :fechaHasta')
               ->setParameter('fechaHasta', new \DateTime($fechaHasta));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Paginación en BD para Cuentas por Cobrar.
     * El filtro por estado usa subconsulta correlated en WHERE para evitar traer todo a PHP.
     *
     * @return array{items: Factura[], totalItems: int}
     */
    public function findPaginatedForCuentasCobrar(
        ?string $sede = null,
        ?string $operacionUuid = null,
        ?string $clienteUuid = null,
        ?string $search = null,
        ?string $estado = null,
        int $page = 0,
        int $itemsPerPage = 20,
    ): array {
        $qb = $this->createQueryBuilder('f')
            ->select(['f', 'd', 'c', 'o', 'pagos', 'v'])
            ->leftJoin('f.despacho', 'd')
            ->leftJoin('d.cliente', 'c')
            ->leftJoin('d.operacion', 'o')
            ->leftJoin('f.pagos', 'pagos')
            ->leftJoin('pagos.voucher', 'v')
            ->where('f.isActive = true')
            ->andWhere('f.isAnulada = false')
            ->orderBy('f.fechaEmision', 'DESC');

        if ($sede !== null) {
            $qb->andWhere('d.sede = :sede')->setParameter('sede', $sede);
        }

        if ($operacionUuid !== null) {
            $qb->andWhere('o.uuid = :operacionUuid')
               ->setParameter('operacionUuid', $operacionUuid, UidType::NAME);
        }

        if ($clienteUuid !== null) {
            $qb->andWhere('c.uuid = :clienteUuid')
               ->setParameter('clienteUuid', $clienteUuid, UidType::NAME);
        }

        if ($search !== null) {
            $qb->andWhere('f.numeroDocumento LIKE :s OR c.razonSocial LIKE :s OR f.contenedor LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        if ($estado !== null) {
            $this->applyEstadoFilter($qb, $estado);
        }

        $query = $qb->getQuery()
            ->setFirstResult($page * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $paginator = new DoctrinePaginator($query, fetchJoinCollection: true);

        return [
            'items'      => iterator_to_array($paginator->getIterator()),
            'totalItems' => count($paginator),
        ];
    }

    private function applyEstadoFilter(QueryBuilder $qb, string $estado): void
    {
        $montoPagadoSub = '(SELECT COALESCE(SUM(pSub.montoAplicado), 0)'
            . ' FROM App\apps\core\Entity\PagoFactura pSub'
            . ' WHERE pSub.factura = f AND pSub.isActive = true)';

        $hoy = new \DateTimeImmutable('today');

        match ($estado) {
            'PAGADO' => $qb->andWhere("f.total IS NOT NULL AND $montoPagadoSub >= f.total - 0.001"),
            'VENCIDA' => $qb
                ->andWhere("(f.total IS NULL OR $montoPagadoSub < f.total - 0.001)")
                ->andWhere('f.fechaVencimiento IS NOT NULL')
                ->andWhere('f.fechaVencimiento < :hoy')
                ->setParameter('hoy', $hoy),
            'PENDIENTE' => $qb
                ->andWhere("(f.total IS NULL OR $montoPagadoSub < f.total - 0.001)")
                ->andWhere('f.fechaVencimiento IS NULL OR f.fechaVencimiento >= :hoy')
                ->setParameter('hoy', $hoy),
            default => null,
        };
    }

    public function getTotales(
        ?bool $isAnulada = null,
        ?string $tipoServicio = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $search = null,
    ): array {
        $qb = $this->createQueryBuilder('factura')
            ->select([
                'SUM(CASE WHEN factura.isAnulada = false AND (factura.moneda = \'USD\' OR factura.moneda IS NULL) THEN factura.importe ELSE 0 END) AS totalImporteUsd',
                'SUM(CASE WHEN factura.isAnulada = false AND (factura.moneda = \'USD\' OR factura.moneda IS NULL) THEN factura.igv ELSE 0 END) AS totalIgvUsd',
                'SUM(CASE WHEN factura.isAnulada = false AND (factura.moneda = \'USD\' OR factura.moneda IS NULL) THEN factura.total ELSE 0 END) AS totalGeneralUsd',
                'SUM(CASE WHEN factura.isAnulada = false AND factura.moneda = \'PEN\' THEN factura.importe ELSE 0 END) AS totalImportePen',
                'SUM(CASE WHEN factura.isAnulada = false AND factura.moneda = \'PEN\' THEN factura.igv ELSE 0 END) AS totalIgvPen',
                'SUM(CASE WHEN factura.isAnulada = false AND factura.moneda = \'PEN\' THEN factura.total ELSE 0 END) AS totalGeneralPen',
                'SUM(CASE WHEN factura.isAnulada = false THEN 1 ELSE 0 END) AS countActivas',
                'SUM(CASE WHEN factura.isAnulada = true THEN 1 ELSE 0 END) AS countAnuladas',
                'SUM(CASE WHEN factura.isAnulada = false AND (factura.moneda = \'USD\' OR factura.moneda IS NULL) THEN 1 ELSE 0 END) AS countActivasUsd',
                'SUM(CASE WHEN factura.isAnulada = false AND factura.moneda = \'PEN\' THEN 1 ELSE 0 END) AS countActivasPen',
            ])
            ->leftJoin('factura.despacho', 'despacho')
            ->leftJoin('despacho.cliente', 'cliente')
            ->where('factura.isActive = true');

        if ($isAnulada !== null) {
            $qb->andWhere('factura.isAnulada = :isAnulada')
               ->setParameter('isAnulada', $isAnulada);
        }

        if ($tipoServicio) {
            $qb->andWhere('factura.tipoServicio = :tipoServicio')
               ->setParameter('tipoServicio', $tipoServicio);
        }

        if ($fechaDesde) {
            $qb->andWhere('factura.fechaEmision >= :fechaDesde')
               ->setParameter('fechaDesde', $fechaDesde);
        }

        if ($fechaHasta) {
            $qb->andWhere('factura.fechaEmision <= :fechaHasta')
               ->setParameter('fechaHasta', $fechaHasta);
        }

        if ($search) {
            $qb->andWhere('factura.numeroDocumento LIKE :s OR factura.serie LIKE :s OR factura.contenedor LIKE :s OR cliente.razonSocial LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        $row = $qb->getQuery()->getSingleResult();

        return [
            'totalImporteUsd'  => (float) ($row['totalImporteUsd'] ?? 0),
            'totalIgvUsd'      => (float) ($row['totalIgvUsd'] ?? 0),
            'totalGeneralUsd'  => (float) ($row['totalGeneralUsd'] ?? 0),
            'totalImportePen'  => (float) ($row['totalImportePen'] ?? 0),
            'totalIgvPen'      => (float) ($row['totalIgvPen'] ?? 0),
            'totalGeneralPen'  => (float) ($row['totalGeneralPen'] ?? 0),
            'countActivas'     => (int)   ($row['countActivas'] ?? 0),
            'countAnuladas'    => (int)   ($row['countAnuladas'] ?? 0),
            'countActivasUsd'  => (int)   ($row['countActivasUsd'] ?? 0),
            'countActivasPen'  => (int)   ($row['countActivasPen'] ?? 0),
        ];
    }

    public function findByDespachoAndNumeroDocumento(object $despacho, string $numeroDocumento): ?Factura
    {
        return $this->createQueryBuilder('f')
            ->where('f.despacho = :despacho')
            ->andWhere('f.numeroDocumento = :numero')
            ->andWhere('f.isActive = true')
            ->setParameter('despacho', $despacho)
            ->setParameter('numero', $numeroDocumento)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
