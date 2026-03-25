<?php

namespace App\apps\core\Service\Despacho\Dto;

use App\apps\core\Entity\Despacho;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class DespachoDtoTransformer extends DtoTransformer
{
    /** @param Despacho $object */
    public function fromObject(mixed $object): ?DespachoDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new DespachoDto();
        $dto->numeroCliente = $object->getNumeroCliente();
        $dto->numeroPlanta = $object->getNumeroPlanta();
        $dto->sede = $object->getSede();
        $dto->contenedor = $object->getContenedor();
        $dto->observaciones = $object->getObservaciones();

        if ($object->getCliente()) {
            $dto->clienteId = UidType::toString($object->getCliente()->uuid());
            $dto->clienteRuc = $object->getCliente()->getRuc();
            $dto->clienteRazonSocial = $object->getCliente()->getRazonSocial();
        }

        if ($object->getFruta()) {
            $dto->frutaId = UidType::toString($object->getFruta()->uuid());
            $dto->frutaNombre = $object->getFruta()->getNombre();
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
