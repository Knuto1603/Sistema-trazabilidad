<?php

namespace App\apps\core\Service\Despacho\Dto;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\FrutaRepository;

final readonly class DespachoFactory
{
    public function __construct(
        private ClienteRepository $clienteRepository,
        private FrutaRepository $frutaRepository,
    ) {
    }

    public function ofDto(?DespachoDto $dto): ?Despacho
    {
        if (null === $dto) {
            return null;
        }

        $despacho = new Despacho();
        $this->updateOfDto($dto, $despacho);

        return $despacho;
    }

    public function updateOfDto(DespachoDto $dto, Despacho $despacho): void
    {
        if ($dto->clienteId) {
            $cliente = $this->clienteRepository->ofId($dto->clienteId, true);
            $despacho->setCliente($cliente);
        }

        if ($dto->frutaId) {
            $fruta = $this->frutaRepository->ofId($dto->frutaId, true);
            $despacho->setFruta($fruta);
        }

        $despacho->setSede($dto->sede);
        $despacho->setContenedor($dto->contenedor);
        $despacho->setObservaciones($dto->observaciones);

        match ($dto->isActive) {
            false => $despacho->disable(),
            default => $despacho->enable(),
        };
    }
}
