<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Repository\ClienteRepository;

final readonly class DeleteClienteService
{
    public function __construct(
        private ClienteRepository $clienteRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->clienteRepository->removeById($id);
    }
}
