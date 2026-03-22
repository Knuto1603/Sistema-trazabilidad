<?php

namespace App\apps\core\Service\Parametro\Dto;

use App\apps\core\Entity\Parametro;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class ParametroDtoTransformer extends DtoTransformer
{
    /** @param Parametro $object */
    public function fromObject(mixed $object): ?ParametroDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new ParametroDto();
        $dto->name = $object->getName();
        $dto->alias = $object->getAlias();
        $dto->value = $object->getValue() ? (float) $object->getValue() : null;
        $dto->parentId = UidType::toString($object->getParent()?->uuid());
        $dto->parentName = $object->getParent()?->getName();
        $dto->ofEntity($object);

        return $dto;
    }
}
