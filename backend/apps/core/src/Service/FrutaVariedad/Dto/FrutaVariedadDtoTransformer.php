<?php

namespace App\apps\core\Service\FrutaVariedad\Dto;

use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class FrutaVariedadDtoTransformer extends DtoTransformer
{
    public function fromObject(mixed $object): ?FrutaVariedadDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new FrutaVariedadDto();
        $dto->nombre = $object->getNombre();
        $dto->frutaId = UidType::toString($object->getFruta()?->uuid());
        $dto->frutaNombre = $object->getFruta()?->getNombre();
        $dto->ofEntity($object);

        return $dto;
    }
}
