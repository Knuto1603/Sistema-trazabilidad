<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Entity\Cliente;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Service\Cliente\Dto\ClienteDto;
use App\apps\core\Service\Cliente\Dto\ClienteFactory;
use App\shared\Exception\RepositoryException;

final readonly class CreateClienteService
{
    public function __construct(
        private ClienteRepository $clienteRepository,
        private ClienteFactory $clienteFactory,
    ) {
    }

    public function execute(ClienteDto $dto): Cliente
    {
        if (null !== $this->clienteRepository->findByRuc($dto->ruc)) {
            throw new RepositoryException(\sprintf('El RUC %s ya está registrado', $dto->ruc));
        }

        $cliente = $this->clienteFactory->ofDto($dto);
        $this->clienteRepository->save($cliente);

        return $cliente;
    }
}
