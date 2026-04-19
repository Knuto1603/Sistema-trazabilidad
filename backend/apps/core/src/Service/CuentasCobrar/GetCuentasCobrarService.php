<?php

namespace App\apps\core\Service\CuentasCobrar;

use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\CuentasCobrar\Dto\CuentaCobrarDto;
use App\apps\core\Service\CuentasCobrar\Filter\CuentasCobrarFilterDto;
use App\apps\core\Service\PagoFactura\Dto\PagoFacturaDtoTransformer;
use App\shared\Doctrine\UidType;

readonly class GetCuentasCobrarService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private PagoFacturaDtoTransformer $pagoTransformer,
    ) {}

    public function execute(CuentasCobrarFilterDto $filter): array
    {
        $qb = $this->facturaRepository->createQueryBuilderWithPagos()
            ->where('f.isActive = true')
            ->andWhere('f.isAnulada = false');

        if ($filter->sede) {
            $qb->andWhere('d.sede = :sede')
               ->setParameter('sede', $filter->sede);
        }

        if ($filter->operacionId) {
            $qb->andWhere('o.uuid = :operacionUuid')
               ->setParameter('operacionUuid', $filter->operacionId, UidType::NAME);
        }

        if ($filter->clienteId) {
            $qb->andWhere('c.uuid = :clienteUuid')
               ->setParameter('clienteUuid', $filter->clienteId, UidType::NAME);
        }

        if ($filter->search) {
            $qb->andWhere('f.numeroDocumento LIKE :s OR c.razonSocial LIKE :s OR f.contenedor LIKE :s')
               ->setParameter('s', '%' . $filter->search . '%');
        }

        $facturas = $qb->getQuery()->getResult();

        $hoy = new \DateTime('today');
        $items = [];

        foreach ($facturas as $factura) {
            $dto = new CuentaCobrarDto();
            $dto->id = UidType::toString($factura->uuid());
            $dto->tipoDocumento = $factura->getTipoDocumento();
            $dto->numeroDocumento = $factura->getNumeroDocumento();
            $dto->fechaEmision = $factura->getFechaEmision()?->format('Y-m-d');
            $dto->fechaVencimiento = $factura->getFechaVencimiento()?->format('Y-m-d');
            $dto->total = $factura->getTotal() !== null ? (float) $factura->getTotal() : null;
            $dto->moneda = $factura->getMoneda();
            $dto->contenedor = $factura->getContenedor();
            $dto->numeroGuia = $factura->getNumeroGuia();

            $despacho = $factura->getDespacho();
            if ($despacho) {
                $dto->despachoId = UidType::toString($despacho->uuid());
                $dto->despachoNumero = $despacho->getNumeroCliente();
                $dto->sede = $despacho->getSede();

                if ($despacho->getCliente()) {
                    $dto->clienteId = UidType::toString($despacho->getCliente()->uuid());
                    $dto->clienteRazonSocial = $despacho->getCliente()->getRazonSocial();
                    $dto->clienteRuc = $despacho->getCliente()->getRuc();
                }

                if ($despacho->getOperacion()) {
                    $dto->operacionId = UidType::toString($despacho->getOperacion()->uuid());
                    $dto->operacionNombre = $despacho->getOperacion()->getNombre();
                }
            }

            // Calcular montoPagado (solo pagos activos)
            $montoPagado = 0.0;
            foreach ($factura->getPagos() as $pago) {
                if ($pago->isActive()) {
                    $montoPagado += (float) $pago->getMontoAplicado();
                }
            }

            $dto->montoPagado = round($montoPagado, 2);
            $dto->montoPendiente = $dto->total !== null ? round($dto->total - $montoPagado, 2) : 0.0;

            // Calcular estado
            if ($dto->total !== null && $montoPagado >= $dto->total - 0.001) {
                $dto->estado = 'PAGADO';
            } elseif ($dto->fechaVencimiento && new \DateTime($dto->fechaVencimiento) < $hoy) {
                $dto->estado = 'VENCIDA';
            } else {
                $dto->estado = 'PENDIENTE';
            }

            // Todos los pagos (activos e inactivos) para historial
            $dto->pagos = $this->pagoTransformer->fromObjects($factura->getPagos()->toArray());

            $items[] = $dto;
        }

        // Filtrar por estado en PHP (ya que es campo computado)
        if ($filter->estado) {
            $items = array_values(array_filter($items, fn($i) => $i->estado === $filter->estado));
        }

        // Paginación manual
        $totalItems = count($items);
        $itemsPerPage = $filter->itemsPerPage;
        $page = $filter->page;
        $offset = $page * $itemsPerPage;
        $paginatedItems = array_slice($items, $offset, $itemsPerPage);

        return [
            'items' => $paginatedItems,
            'pagination' => [
                'page'         => $page,
                'itemsPerPage' => $itemsPerPage,
                'count'        => count($paginatedItems),
                'totalItems'   => $totalItems,
                'startIndex'   => $totalItems > 0 ? $offset + 1 : 0,
                'endIndex'     => min($offset + $itemsPerPage, $totalItems),
            ],
        ];
    }
}
