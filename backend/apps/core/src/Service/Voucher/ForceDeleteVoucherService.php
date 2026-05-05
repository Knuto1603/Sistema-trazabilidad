<?php

namespace App\apps\core\Service\Voucher;

use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\PagoFacturaRepository;
use App\apps\core\Repository\VoucherRepository;

readonly class ForceDeleteVoucherService
{
    public function __construct(
        private VoucherRepository $voucherRepository,
        private PagoFacturaRepository $pagoRepository,
    ) {}

    public function execute(string $voucherUuid, string $justificante): Voucher
    {
        if (trim($justificante) === '') {
            throw new \RuntimeException('Se requiere un justificante para eliminar un voucher con pagos asociados.');
        }

        $voucher = $this->voucherRepository->findWithPagos($voucherUuid);

        if ($voucher === null) {
            throw new \RuntimeException('Voucher no encontrado.');
        }

        foreach ($voucher->getPagos() as $pago) {
            if ($pago->isActive()) {
                $pago->disable();
                $pago->setJustificanteEliminacion(trim($justificante));
                $this->pagoRepository->save($pago, flush: false);
            }
        }

        $voucher->disable();
        $this->voucherRepository->save($voucher);

        return $voucher;
    }
}
