<?php

namespace App\apps\core\Service\CuentasCobrar;

use App\apps\core\Entity\Factura;
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
        $result = $this->facturaRepository->findPaginatedForCuentasCobrar(
            sede: $filter->sede,
            operacionUuid: $filter->operacionId,
            clienteUuid: $filter->clienteId,
            search: $filter->search,
            estado: $filter->estado,
            page: $filter->page,
            itemsPerPage: $filter->itemsPerPage,
        );

        $hoy = new \DateTimeImmutable('today');
        $items = array_map(
            fn(Factura $factura) => $this->buildDto($factura, $hoy),
            $result['items'],
        );

        $page = $filter->page;
        $itemsPerPage = $filter->itemsPerPage;
        $totalItems = $result['totalItems'];
        $offset = $page * $itemsPerPage;

        return [
            'items' => $items,
            'pagination' => [
                'page'         => $page,
                'itemsPerPage' => $itemsPerPage,
                'count'        => count($items),
                'totalItems'   => $totalItems,
                'startIndex'   => $totalItems > 0 ? $offset + 1 : 0,
                'endIndex'     => min($offset + $itemsPerPage, $totalItems),
            ],
        ];
    }

    private function buildDto(Factura $factura, \DateTimeImmutable $hoy): CuentaCobrarDto
    {
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
        $dto->detalle = $factura->getDetalle();
        $dto->cajas = $factura->getCajas();
        $dto->kgCaja = $factura->getKgCaja();
        $dto->importe = $factura->getImporte() !== null ? (float) $factura->getImporte() : null;
        $dto->igv = $factura->getIgv() !== null ? (float) $factura->getIgv() : null;
        $dto->tipoCambio = $factura->getTipoCambio() !== null ? (float) $factura->getTipoCambio() : null;
        $dto->tipoServicio = $factura->getTipoServicio();
        $dto->destino = $factura->getDestino();

        $despacho = $factura->getDespacho();
        if ($despacho !== null) {
            $dto->despachoId = UidType::toString($despacho->uuid());
            $dto->despachoNumero = $despacho->getNumeroCliente();
            $dto->sede = $despacho->getSede();

            $cliente = $despacho->getCliente();
            if ($cliente !== null) {
                $dto->clienteId = UidType::toString($cliente->uuid());
                $dto->clienteRazonSocial = $cliente->getRazonSocial();
                $dto->clienteRuc = $cliente->getRuc();
            }

            $operacion = $despacho->getOperacion();
            if ($operacion !== null) {
                $dto->operacionId = UidType::toString($operacion->uuid());
                $dto->operacionNombre = $operacion->getNombre();
            }
        }

        $clienteFactura = $factura->getClienteFactura();
        if ($clienteFactura !== null) {
            $dto->clienteFacturaId = UidType::toString($clienteFactura->uuid());
            $dto->clienteFacturaRazonSocial = $clienteFactura->getRazonSocial();
            $dto->clienteFacturaRuc = $clienteFactura->getRuc();
        }

        $montoPagado = 0.0;
        foreach ($factura->getPagos() as $pago) {
            if ($pago->isActive()) {
                $montoPagado += (float) $pago->getMontoAplicado();
            }
        }

        $dto->montoPagado = round($montoPagado, 2);
        $dto->montoPendiente = $dto->total !== null ? round($dto->total - $montoPagado, 2) : 0.0;
        $dto->estado = $this->calcularEstado($dto->total, $montoPagado, $dto->fechaVencimiento, $hoy);
        $dto->pagos = $this->pagoTransformer->fromObjects($factura->getPagos()->toArray());

        return $dto;
    }

    private function calcularEstado(
        ?float $total,
        float $montoPagado,
        ?string $fechaVencimiento,
        \DateTimeImmutable $hoy,
    ): string {
        if ($total !== null && $montoPagado >= $total - 0.001) {
            return 'PAGADO';
        }

        if ($fechaVencimiento !== null && new \DateTimeImmutable($fechaVencimiento) < $hoy) {
            return 'VENCIDA';
        }

        return 'PENDIENTE';
    }
}
