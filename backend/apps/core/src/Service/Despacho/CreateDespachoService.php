<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\OperacionRepository;
use App\apps\core\Service\Despacho\Dto\DespachoDto;
use App\apps\core\Service\Despacho\Dto\DespachoFactory;

final readonly class CreateDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private DespachoFactory $despachoFactory,
        private ClienteRepository $clienteRepository,
        private OperacionRepository $operacionRepository,
    ) {
    }

    public function execute(DespachoDto $dto): Despacho
    {
        $cliente = $this->clienteRepository->ofId($dto->clienteId, true);

        if ($dto->operacionId !== null) {
            $operacion = $this->operacionRepository->ofId($dto->operacionId, true);
            $numeroCliente = $this->despachoRepository->findMaxNumeroClienteByOperacion(
                $cliente->getId(),
                $operacion->getId()
            ) + 1;
            $numeroPlanta = $this->despachoRepository->findMaxNumeroPlantaByOperacion(
                $operacion->getId()
            ) + 1;
        } else {
            // Comportamiento legacy para despachos sin operación
            $numeroCliente = $this->despachoRepository->findMaxNumeroCliente($cliente->getId()) + 1;
            $numeroPlanta  = $this->despachoRepository->findMaxNumeroPlanta() + 1;
        }

        $despacho = $this->despachoFactory->ofDto($dto);
        // Si el DTO trae valores explícitos, usarlos; si no, usar el autogenerado
        $despacho->setNumeroCliente($dto->numeroCliente ?? $numeroCliente);
        $despacho->setNumeroPlanta($dto->numeroPlanta ?? $numeroPlanta);

        $this->despachoRepository->save($despacho);

        return $despacho;
    }
}
