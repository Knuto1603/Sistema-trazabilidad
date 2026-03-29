<?php

namespace App\apps\core\Service\Operacion;

use App\apps\core\Entity\Operacion;
use App\apps\core\Repository\OperacionRepository;
use App\apps\core\Service\Operacion\Dto\OperacionDto;
use App\apps\core\Service\Operacion\Dto\OperacionFactory;
use App\shared\Exception\RepositoryException;

final readonly class CreateOperacionService
{
    public function __construct(
        private OperacionRepository $operacionRepository,
        private OperacionFactory $operacionFactory,
    ) {
    }

    public function execute(OperacionDto $dto): Operacion
    {
        $exists = $this->operacionRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.nombre = :nombre AND o.sede = :sede')
            ->setParameter('nombre', $dto->nombre)
            ->setParameter('sede', $dto->sede)
            ->getQuery()
            ->getSingleScalarResult();

        if ($exists > 0) {
            throw new RepositoryException(
                sprintf('Ya existe la operación "%s" para la sede %s', $dto->nombre, $dto->sede)
            );
        }

        $operacion = $this->operacionFactory->ofDto($dto);
        $this->operacionRepository->save($operacion);

        return $operacion;
    }
}
