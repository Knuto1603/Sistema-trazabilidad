<?php

namespace App\apps\core\Service\Operacion;

use App\apps\core\Entity\Operacion;
use App\apps\core\Repository\OperacionRepository;
use App\apps\core\Service\Operacion\Dto\OperacionDto;
use App\apps\core\Service\Operacion\Dto\OperacionFactory;

final readonly class UpdateOperacionService
{
    public function __construct(
        private OperacionRepository $operacionRepository,
        private OperacionFactory $operacionFactory,
    ) {
    }

    public function execute(string $id, OperacionDto $dto): Operacion
    {
        $operacion = $this->operacionRepository->ofId($id, true);
        $this->operacionFactory->updateOfDto($dto, $operacion);
        $this->operacionRepository->save($operacion);

        return $operacion;
    }
}
