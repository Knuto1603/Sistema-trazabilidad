<?php

namespace App\apps\core\Service\PagoFactura;

use App\apps\core\Entity\PagoFactura;
use App\apps\core\Repository\PagoFacturaRepository;
use App\apps\core\Repository\VoucherRepository;

readonly class UpdatePagoFacturaService
{
    public function __construct(
        private PagoFacturaRepository $pagoRepository,
        private VoucherRepository $voucherRepository,
    ) {}

    /**
     * @param array{
     *   montoAplicado: float,
     *   voucherNumeroOperacion: ?string
     * } $data
     */
    public function execute(string $pagoId, array $data): PagoFactura
    {
        $pago = $this->pagoRepository->ofId($pagoId, true);

        if (!$pago->isActive()) {
            throw new \RuntimeException('No se puede editar un pago eliminado.');
        }

        $nuevoMonto = (float) $data['montoAplicado'];

        if ($nuevoMonto <= 0) {
            throw new \RuntimeException('El monto debe ser mayor a cero.');
        }

        // Al actualizar: el saldo disponible del voucher es el actual + lo que tenía este pago
        $voucher = $pago->getVoucher();
        $montoDisponible = $voucher->getMontoRestante() + (float) $pago->getMontoAplicado();

        if ($nuevoMonto > $montoDisponible + 0.001) {
            throw new \RuntimeException(
                sprintf('El monto (%.2f) supera el saldo disponible del voucher (%.2f).', $nuevoMonto, $montoDisponible)
            );
        }

        $pago->setMontoAplicado($nuevoMonto);

        if (isset($data['voucherNumeroOperacion'])) {
            $voucher->setNumeroOperacion($data['voucherNumeroOperacion']);
            $this->voucherRepository->save($voucher);
        }

        $this->pagoRepository->save($pago);

        return $pago;
    }
}
