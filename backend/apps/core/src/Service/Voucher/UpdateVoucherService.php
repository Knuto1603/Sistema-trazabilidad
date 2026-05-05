<?php

namespace App\apps\core\Service\Voucher;

use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\VoucherRepository;

readonly class UpdateVoucherService
{
    public function __construct(
        private VoucherRepository $voucherRepository,
    ) {}

    public function execute(string $voucherUuid, array $data): Voucher
    {
        $voucher = $this->voucherRepository->ofId($voucherUuid, true);

        if ($voucher->getMontoUsado() > 0 && (float) $data['montoTotal'] < $voucher->getMontoUsado()) {
            throw new \RuntimeException(
                sprintf(
                    'El monto total no puede ser menor al monto ya utilizado (%.2f).',
                    $voucher->getMontoUsado()
                )
            );
        }

        $voucher->setNumero(trim($data['numero']));
        $voucher->setNumeroOperacion(!empty($data['numeroOperacion']) ? trim($data['numeroOperacion']) : null);
        $voucher->setMontoTotal((float) $data['montoTotal']);
        $voucher->setFecha(new \DateTime($data['fecha']));

        $this->voucherRepository->save($voucher);

        return $voucher;
    }
}
