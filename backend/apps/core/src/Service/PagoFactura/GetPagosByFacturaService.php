<?php

namespace App\apps\core\Service\PagoFactura;

use App\apps\core\Repository\PagoFacturaRepository;
use App\apps\core\Service\PagoFactura\Dto\PagoFacturaDtoTransformer;

readonly class GetPagosByFacturaService
{
    public function __construct(
        private PagoFacturaRepository $pagoRepository,
        private PagoFacturaDtoTransformer $transformer,
    ) {}

    public function execute(string $facturaUuid): array
    {
        $pagos = $this->pagoRepository->findByFacturaUuid($facturaUuid);
        return $this->transformer->fromObjects($pagos);
    }
}
