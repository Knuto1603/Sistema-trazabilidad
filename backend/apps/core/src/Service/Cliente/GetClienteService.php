<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Entity\Cliente;
use App\apps\core\Repository\ClienteRepository;

final readonly class GetClienteService
{
    public function __construct(
        private ClienteRepository $clienteRepository,
    ) {
    }

    public function execute(string $id): Cliente
    {
        return $this->clienteRepository->ofId($id, true);
    }
}
