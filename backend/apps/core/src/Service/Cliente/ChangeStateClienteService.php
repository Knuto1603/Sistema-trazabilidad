<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Entity\Cliente;
use App\apps\core\Repository\ClienteRepository;

final readonly class ChangeStateClienteService
{
    public function __construct(
        private ClienteRepository $clienteRepository,
    ) {
    }

    public function execute(string $id, bool $isActive): Cliente
    {
        $cliente = $this->clienteRepository->ofId($id, true);
        $cliente->setIsActive($isActive);
        $this->clienteRepository->save($cliente);

        return $cliente;
    }
}
