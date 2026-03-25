<?php

namespace App\apps\core\Service\ArchivoDespacho\Dto;

use App\apps\core\Entity\ArchivoDespacho;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class ArchivoDespachoDtoTransformer extends DtoTransformer
{
    /** @param ArchivoDespacho $object */
    public function fromObject(mixed $object): ?ArchivoDespachoDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new ArchivoDespachoDto();
        $dto->nombre = $object->getNombre();
        $dto->tipoArchivo = $object->getTipoArchivo();
        $dto->ruta = $object->getRuta();
        $dto->tamanho = $object->getTamanho();

        if ($object->getDespacho()) {
            $dto->despachoId = UidType::toString($object->getDespacho()->uuid());
        }

        if ($object->getFactura()) {
            $dto->facturaId = UidType::toString($object->getFactura()->uuid());
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
