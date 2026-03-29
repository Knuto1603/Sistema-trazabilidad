<?php

namespace App\apps\core\Service\Operacion;

use App\apps\core\Repository\OperacionRepository;

final readonly class DeleteOperacionService
{
    public function __construct(
        private OperacionRepository $operacionRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->operacionRepository->removeById($id);
    }
}
