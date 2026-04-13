<?php

namespace App\apps\core\Service\Factura\Dto;

use App\apps\core\Entity\Factura;
use App\apps\core\Repository\DespachoRepository;

final readonly class FacturaFactory
{
    public function __construct(
        private DespachoRepository $despachoRepository,
    ) {
    }

    public function ofDto(?FacturaDto $dto): ?Factura
    {
        if (null === $dto) {
            return null;
        }

        $factura = new Factura();
        $this->updateOfDto($dto, $factura);

        return $factura;
    }

    public function updateOfDto(FacturaDto $dto, Factura $factura): void
    {
        if ($dto->despachoId) {
            $despacho = $this->despachoRepository->ofId($dto->despachoId, true);
            $factura->setDespacho($despacho);
        }

        $factura->setTipoDocumento($dto->tipoDocumento);
        $factura->setSerie(trim($dto->serie ?? ''));
        $factura->setCorrelativo(trim($dto->correlativo ?? ''));
        $factura->setNumeroDocumento(trim($dto->serie ?? '') . '-' . trim($dto->correlativo ?? ''));
        $factura->setNumeroGuia($dto->numeroGuia);
        $factura->setFechaEmision(new \DateTime($dto->fechaEmision));
        $factura->setFechaVencimiento($dto->fechaVencimiento ? new \DateTime($dto->fechaVencimiento) : null);
        $factura->setMoneda($dto->moneda ?? 'USD');
        $factura->setDetalle($dto->detalle);
        if ($dto->kgCaja !== null) $factura->setKgCaja($dto->kgCaja);
        if ($dto->unidadMedida !== null) $factura->setUnidadMedida($dto->unidadMedida);
        if ($dto->cajas !== null) $factura->setCajas($dto->cajas);
        $factura->setCantidad($dto->cantidad);
        $factura->setValorUnitario($dto->valorUnitario);
        $factura->setImporte($dto->importe);
        $factura->setIgv($dto->igv);
        $factura->setTotal($dto->total);
        $factura->setTipoCambio($dto->tipoCambio);
        $factura->setTipoServicio($dto->tipoServicio);
        $factura->setTipoOperacion($dto->tipoOperacion);
        $factura->setIsAnulada($dto->isAnulada);
        $factura->setContenedor($dto->contenedor);
        $factura->setDestino($dto->destino);

        match ($dto->isActive) {
            false => $factura->disable(),
            default => $factura->enable(),
        };
    }
}
