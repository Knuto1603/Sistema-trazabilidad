<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Repository\OperacionRepository;

final readonly class GetProximoNumeroDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private OperacionRepository $operacionRepository,
        private FrutaRepository $frutaRepository,
        private ClienteRepository $clienteRepository,
    ) {}

    /**
     * Calcula el siguiente numeroPlanta disponible.
     * Si se proporciona operacionId, el máximo se calcula dentro de esa operación.
     */
    public function nextNumeroPlanta(?string $operacionUuid, ?string $frutaUuid): int
    {
        if ($operacionUuid === null) {
            return $this->despachoRepository->findMaxNumeroPlanta() + 1;
        }

        $operacion = $this->operacionRepository->ofId($operacionUuid, true);
        $frutaDbId = $this->resolveFrutaDbId($frutaUuid);

        return $this->despachoRepository->findMaxNumeroPlantaByOperacion($operacion->getId(), $frutaDbId) + 1;
    }

    /**
     * Calcula el siguiente numeroCliente disponible para un cliente dado.
     * Si se proporciona operacionId, el máximo se calcula dentro de esa operación.
     * Devuelve 1 si no se proporciona clienteId.
     */
    public function nextNumeroCliente(?string $clienteUuid, ?string $operacionUuid, ?string $frutaUuid): int
    {
        if ($clienteUuid === null) {
            return 1;
        }

        $cliente = $this->clienteRepository->ofId($clienteUuid, true);
        $frutaDbId = $this->resolveFrutaDbId($frutaUuid);

        if ($operacionUuid !== null) {
            $operacion = $this->operacionRepository->ofId($operacionUuid, true);

            return $this->despachoRepository->findMaxNumeroClienteByOperacion(
                $cliente->getId(),
                $operacion->getId(),
                $frutaDbId,
            ) + 1;
        }

        return $this->despachoRepository->findMaxNumeroCliente($cliente->getId()) + 1;
    }

    private function resolveFrutaDbId(?string $frutaUuid): ?int
    {
        if ($frutaUuid === null) {
            return null;
        }

        return $this->frutaRepository->ofId($frutaUuid, true)->getId();
    }
}
