<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Entity\Factura;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\Factura\Dto\FacturaDto;
use App\apps\core\Service\Factura\Dto\FacturaFactory;

final readonly class UpdateFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private FacturaFactory $facturaFactory,
    ) {
    }

    public function execute(string $id, FacturaDto $dto): Factura
    {
        $factura = $this->facturaRepository->ofId($id, true);
        $this->facturaFactory->updateOfDto($dto, $factura);
        $this->facturaRepository->save($factura);

        return $factura;
    }
}
