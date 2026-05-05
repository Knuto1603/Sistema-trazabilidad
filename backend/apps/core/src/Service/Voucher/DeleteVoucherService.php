<?php

namespace App\apps\core\Service\Voucher;

use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\VoucherRepository;

readonly class DeleteVoucherService
{
    public function __construct(
        private VoucherRepository $voucherRepository,
    ) {}

    public function execute(string $voucherUuid): Voucher
    {
        $voucher = $this->voucherRepository->ofId($voucherUuid, true);

        $tienePageosActivos = $voucher->getPagos()->exists(
            fn(int $k, $pago) => $pago->isActive()
        );

        if ($tienePageosActivos) {
            throw new \RuntimeException(
                'No se puede eliminar el voucher porque tiene pagos activos asociados. Anule los pagos primero.'
            );
        }

        $voucher->disable();
        $this->voucherRepository->save($voucher);

        return $voucher;
    }
}
