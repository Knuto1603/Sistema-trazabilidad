<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\FacturaRepository;

final readonly class DeleteFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $factura = $this->facturaRepository->ofId($id, true);
        $this->archivoDespachoRepository->unlinkFactura($factura);
        $this->facturaRepository->removeById($id);
    }
}
