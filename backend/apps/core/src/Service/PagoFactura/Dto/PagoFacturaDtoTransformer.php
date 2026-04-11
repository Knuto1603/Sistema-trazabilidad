<?php

namespace App\apps\core\Service\PagoFactura\Dto;

use App\apps\core\Entity\PagoFactura;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class PagoFacturaDtoTransformer extends DtoTransformer
{
    /** @param PagoFactura $object */
    public function fromObject(mixed $object): ?PagoFacturaDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new PagoFacturaDto();
        $dto->montoAplicado = $object->getMontoAplicado() !== null ? (float) $object->getMontoAplicado() : null;
        $dto->justificanteEliminacion = $object->getJustificanteEliminacion();
        $dto->createdAt = $object->createdAt()?->format('Y-m-d H:i:s');

        $voucher = $object->getVoucher();
        if ($voucher) {
            $dto->voucherId = UidType::toString($voucher->uuid());
            $dto->voucherNumero = $voucher->getNumero();
            $dto->voucherNumeroOperacion = $voucher->getNumeroOperacion();
            $dto->voucherMontoTotal = $voucher->getMontoTotal() !== null ? (float) $voucher->getMontoTotal() : null;
            $dto->voucherMontoRestante = $voucher->getMontoRestante();
            $dto->voucherMontoUsado = $voucher->getMontoUsado();
            $dto->voucherFecha = $voucher->getFecha()?->format('Y-m-d');

            if ($voucher->getCliente()) {
                $dto->voucherClienteId = UidType::toString($voucher->getCliente()->uuid());
            }
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
