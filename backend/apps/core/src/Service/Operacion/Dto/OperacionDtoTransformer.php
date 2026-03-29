<?php

namespace App\apps\core\Service\Operacion\Dto;

use App\apps\core\Entity\Operacion;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class OperacionDtoTransformer extends DtoTransformer
{
    /** @param Operacion $object */
    public function fromObject(mixed $object): ?OperacionDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new OperacionDto();
        $dto->nombre = $object->getNombre();
        $dto->sede = $object->getSede();
        $dto->ofEntity($object);

        return $dto;
    }
}
