<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\DespachoRepository;

final readonly class DeleteDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->despachoRepository->removeById($id);
    }
}
