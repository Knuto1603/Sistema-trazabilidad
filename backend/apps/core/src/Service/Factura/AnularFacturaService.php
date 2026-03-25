<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Entity\Factura;
use App\apps\core\Repository\FacturaRepository;

final readonly class AnularFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
    ) {
    }

    public function execute(string $id): Factura
    {
        $factura = $this->facturaRepository->ofId($id, true);
        $factura->setIsAnulada(true);
        $this->facturaRepository->save($factura);

        return $factura;
    }
}
