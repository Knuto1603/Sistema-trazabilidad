<?php

namespace App\apps\core\Service\Fruta\Dto;

use App\shared\Service\Transformer\DtoTransformer;

class FrutaDtoTransformer extends DtoTransformer
{
    public function __construct()
    {
    }

    public function fromObject(mixed $object): ?FrutaDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new FrutaDto();
        $dto->codigo = $object->getCodigo();
        $dto->nombre = $object->getNombre();
        $dto->ofEntity($object);

        return $dto;
    }

}
