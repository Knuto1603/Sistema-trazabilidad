<?php

namespace App\apps\core\Service\Cliente\Dto;

use App\apps\core\Entity\Cliente;

final readonly class ClienteFactory
{
    public function ofDto(?ClienteDto $dto): ?Cliente
    {
        if (null === $dto) {
            return null;
        }

        $cliente = new Cliente();
        $this->updateOfDto($dto, $cliente);

        return $cliente;
    }

    public function updateOfDto(ClienteDto $dto, Cliente $cliente): void
    {
        $cliente->setRuc($dto->ruc);
        $cliente->setRazonSocial($dto->razonSocial);
        $cliente->setNombreComercial($dto->nombreComercial);
        $cliente->setDireccion($dto->direccion);
        $cliente->setDepartamento($dto->departamento);
        $cliente->setProvincia($dto->provincia);
        $cliente->setDistrito($dto->distrito);
        $cliente->setEstado($dto->estado);
        $cliente->setCondicion($dto->condicion);
        $cliente->setTipoContribuyente($dto->tipoContribuyente);
        $cliente->setTelefono($dto->telefono);
        $cliente->setEmail($dto->email);

        match ($dto->isActive) {
            false => $cliente->disable(),
            default => $cliente->enable(),
        };
    }
}
