<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\FacturaRepository;

final readonly class DeleteFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->facturaRepository->removeById($id);
    }
}
