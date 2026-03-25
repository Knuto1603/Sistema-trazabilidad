<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Service\Despacho\Dto\DespachoDto;
use App\apps\core\Service\Despacho\Dto\DespachoFactory;

final readonly class CreateDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private DespachoFactory $despachoFactory,
        private ClienteRepository $clienteRepository,
    ) {
    }

    public function execute(DespachoDto $dto): Despacho
    {
        $cliente = $this->clienteRepository->ofId($dto->clienteId, true);

        $numeroCliente = $this->despachoRepository->findMaxNumeroCliente($cliente->getId()) + 1;
        $numeroPlanta = $this->despachoRepository->findMaxNumeroPlanta() + 1;

        $despacho = $this->despachoFactory->ofDto($dto);
        $despacho->setNumeroCliente($numeroCliente);
        $despacho->setNumeroPlanta($numeroPlanta);

        $this->despachoRepository->save($despacho);

        return $despacho;
    }
}
