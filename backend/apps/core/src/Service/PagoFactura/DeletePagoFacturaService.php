<?php

namespace App\apps\core\Service\PagoFactura;

use App\apps\core\Entity\PagoFactura;
use App\apps\core\Repository\PagoFacturaRepository;

readonly class DeletePagoFacturaService
{
    public function __construct(
        private PagoFacturaRepository $pagoRepository,
    ) {}

    public function execute(string $pagoId, string $justificante): PagoFactura
    {
        if (trim($justificante) === '') {
            throw new \RuntimeException('Se requiere un justificante para eliminar un pago.');
        }

        $pago = $this->pagoRepository->ofId($pagoId, true);
        $pago->disable();
        $pago->setJustificanteEliminacion(trim($justificante));

        $this->pagoRepository->save($pago);

        return $pago;
    }
}
