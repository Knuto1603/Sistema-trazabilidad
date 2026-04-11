<?php

namespace App\apps\core\Service\Voucher\Dto;

use App\apps\core\Entity\Voucher;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class VoucherDtoTransformer extends DtoTransformer
{
    /** @param Voucher $object */
    public function fromObject(mixed $object): ?VoucherDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new VoucherDto();
        $dto->numero = $object->getNumero();
        $dto->numeroOperacion = $object->getNumeroOperacion();
        $dto->montoTotal = $object->getMontoTotal() !== null ? (float) $object->getMontoTotal() : null;
        $dto->fecha = $object->getFecha()?->format('Y-m-d');
        $dto->montoRestante = $object->getMontoRestante();
        $dto->montoUsado = $object->getMontoUsado();

        if ($object->getCliente()) {
            $dto->clienteId = UidType::toString($object->getCliente()->uuid());
            $dto->clienteRazonSocial = $object->getCliente()->getRazonSocial();
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
