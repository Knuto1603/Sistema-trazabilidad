<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\Factura\Dto\FacturaDtoTransformer;

final readonly class GetFacturasByDespachoService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private FacturaDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(string $despachoUuid): array
    {
        $facturas = $this->facturaRepository->findByDespachoUuid($despachoUuid);

        return array_map(fn ($f) => $this->dtoTransformer->fromObject($f), $facturas);
    }
}
