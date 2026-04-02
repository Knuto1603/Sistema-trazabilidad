<?php

namespace App\apps\core\Service\Despacho\Dto;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Repository\OperacionRepository;

final readonly class DespachoFactory
{
    public function __construct(
        private ClienteRepository $clienteRepository,
        private FrutaRepository $frutaRepository,
        private OperacionRepository $operacionRepository,
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

        if ($dto->numeroPlanta !== null) {
            $despacho->setNumeroPlanta($dto->numeroPlanta);
        }

        if ($dto->numeroCliente !== null) {
            $despacho->setNumeroCliente($dto->numeroCliente);
        }

        if ($dto->operacionId) {
            $operacion = $this->operacionRepository->ofId($dto->operacionId, true);
            $despacho->setOperacion($operacion);
        }

        match ($dto->isActive) {
            false => $despacho->disable(),
            default => $despacho->enable(),
        };
    }
}
