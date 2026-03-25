<?php

namespace App\apps\core\Service\Cliente\Dto;

use App\apps\core\Entity\Cliente;
use App\shared\Service\Transformer\DtoTransformer;

final class ClienteDtoTransformer extends DtoTransformer
{
    /** @param Cliente $object */
    public function fromObject(mixed $object): ?ClienteDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new ClienteDto();
        $dto->ruc = $object->getRuc();
        $dto->razonSocial = $object->getRazonSocial();
        $dto->nombreComercial = $object->getNombreComercial();
        $dto->direccion = $object->getDireccion();
        $dto->departamento = $object->getDepartamento();
        $dto->provincia = $object->getProvincia();
        $dto->distrito = $object->getDistrito();
        $dto->estado = $object->getEstado();
        $dto->condicion = $object->getCondicion();
        $dto->tipoContribuyente = $object->getTipoContribuyente();
        $dto->telefono = $object->getTelefono();
        $dto->email = $object->getEmail();

        $dto->ofEntity($object);

        return $dto;
    }
}
