<?php

namespace App\apps\core\Service\TipoCambio\Dto;

use App\apps\core\Entity\TipoCambio;
use App\shared\Service\Transformer\DtoTransformer;

final class TipoCambioDtoTransformer extends DtoTransformer
{
    /** @param TipoCambio $object */
    public function fromObject(mixed $object): ?TipoCambioDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new TipoCambioDto();
        $dto->fecha = $object->getFecha()->format('Y-m-d');
        $dto->compra = (float) $object->getCompra();
        $dto->venta = (float) $object->getVenta();

        $dto->ofEntity($object);

        return $dto;
    }
}
