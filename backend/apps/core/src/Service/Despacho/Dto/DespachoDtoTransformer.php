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
        $dto->fechaDespacho = $object->getFechaDespacho()?->format('Y-m-d');

        if ($object->getCliente()) {
            $dto->clienteId = UidType::toString($object->getCliente()->uuid());
            $dto->clienteRuc = $object->getCliente()->getRuc();
            $dto->clienteRazonSocial = $object->getCliente()->getRazonSocial();
        }

        if ($object->getFruta()) {
            $dto->frutaId = UidType::toString($object->getFruta()->uuid());
            $dto->frutaNombre = $object->getFruta()->getNombre();
        }

        if ($object->getOperacion()) {
            $dto->operacionId = UidType::toString($object->getOperacion()->uuid());
            $dto->operacionNombre = $object->getOperacion()->getNombre();
        }

        if ($object->getCampahna()) {
            $dto->campanhaId = UidType::toString($object->getCampahna()->uuid());
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
