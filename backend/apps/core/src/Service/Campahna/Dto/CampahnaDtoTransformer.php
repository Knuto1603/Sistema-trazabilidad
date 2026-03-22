<?php

namespace App\apps\core\Service\Campahna\Dto;

use App\apps\core\Entity\Campahna;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class CampahnaDtoTransformer extends DtoTransformer
{
    /** @param Campahna $object */
    public function fromObject(mixed $object): ?CampahnaDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new CampahnaDto();
        $dto->nombre = $object->getNombre();
        $dto->descripcion = $object->getDescripcion();

        // Transformación de fechas a formato string para el DTO
        $dto->fechaInicio = $object->getFechaInicio()?->format('Y-m-d');
        $dto->fechaFin = $object->getFechaFin()?->format('Y-m-d');

        // Mapeo de Fruta (Producto)
        $dto->frutaId = UidType::toString($object->getFruta()?->uuid());
        $dto->frutaNombre = $object->getFruta()?->getNombre();

        // Información consolidada para el frontend
        $dto->nombreCompleto = $object->getNombreCompleto();

        $dto->ofEntity($object);

        return $dto;
    }
}
