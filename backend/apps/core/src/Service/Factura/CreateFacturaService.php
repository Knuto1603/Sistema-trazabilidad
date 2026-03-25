<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Entity\Factura;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\Factura\Dto\FacturaDto;
use App\apps\core\Service\Factura\Dto\FacturaFactory;

final readonly class CreateFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private FacturaFactory $facturaFactory,
    ) {
    }

    public function execute(FacturaDto $dto): Factura
    {
        $factura = $this->facturaFactory->ofDto($dto);
        $this->facturaRepository->save($factura);

        return $factura;
    }
}
