<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Entity\Cliente;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Service\Cliente\Dto\ClienteDto;
use App\apps\core\Service\Cliente\Dto\ClienteFactory;
use App\shared\Exception\RepositoryException;

final readonly class UpdateClienteService
{
    public function __construct(
        private ClienteRepository $clienteRepository,
        private ClienteFactory $clienteFactory,
    ) {
    }

    public function execute(string $id, ClienteDto $dto): Cliente
    {
        $cliente = $this->clienteRepository->ofId($id, true);

        if ($cliente->getRuc() !== $dto->ruc) {
            $existing = $this->clienteRepository->findByRuc($dto->ruc);
            if (null !== $existing && $existing->getId() !== $cliente->getId()) {
                throw new RepositoryException(\sprintf('El RUC %s ya está registrado', $dto->ruc));
            }
        }

        $this->clienteFactory->updateOfDto($dto, $cliente);
        $this->clienteRepository->save($cliente);

        return $cliente;
    }
}
