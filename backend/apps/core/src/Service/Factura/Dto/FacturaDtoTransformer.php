<?php

namespace App\apps\core\Service\Factura\Dto;

use App\apps\core\Entity\Factura;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class FacturaDtoTransformer extends DtoTransformer
{
    /** @param Factura $object */
    public function fromObject(mixed $object): ?FacturaDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new FacturaDto();
        $dto->tipoDocumento = $object->getTipoDocumento();
        $dto->serie = $object->getSerie();
        $dto->correlativo = $object->getCorrelativo();
        $dto->numeroDocumento = $object->getNumeroDocumento();
        $dto->numeroGuia = $object->getNumeroGuia();
        $dto->fechaEmision = $object->getFechaEmision()?->format('Y-m-d');
        $dto->moneda = $object->getMoneda();
        $dto->detalle = $object->getDetalle();
        $dto->kgCaja = $object->getKgCaja();
        $dto->unidadMedida = $object->getUnidadMedida();
        $dto->cajas = $object->getCajas();
        $dto->cantidad = $object->getCantidad() !== null ? (float) $object->getCantidad() : null;
        $dto->valorUnitario = $object->getValorUnitario() !== null ? (float) $object->getValorUnitario() : null;
        $dto->importe = $object->getImporte() !== null ? (float) $object->getImporte() : null;
        $dto->igv = $object->getIgv() !== null ? (float) $object->getIgv() : null;
        $dto->total = $object->getTotal() !== null ? (float) $object->getTotal() : null;
        $dto->tipoCambio = $object->getTipoCambio() !== null ? (float) $object->getTipoCambio() : null;
        $dto->tipoServicio = $object->getTipoServicio();
        $dto->tipoOperacion = $object->getTipoOperacion();
        $dto->isAnulada = $object->isAnulada();
        $dto->contenedor = $object->getContenedor();
        $dto->destino = $object->getDestino();

        if ($object->getDespacho()) {
            $dto->despachoId = UidType::toString($object->getDespacho()->uuid());
            $dto->despachoNumero = $object->getDespacho()->getNumeroCliente();

            if ($object->getDespacho()->getCliente()) {
                $dto->clienteRazonSocial = $object->getDespacho()->getCliente()->getRazonSocial();
            }
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
