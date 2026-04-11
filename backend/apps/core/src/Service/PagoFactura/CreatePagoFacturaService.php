<?php

namespace App\apps\core\Service\PagoFactura;

use App\apps\core\Entity\PagoFactura;
use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Repository\PagoFacturaRepository;
use App\apps\core\Repository\VoucherRepository;
use App\shared\Doctrine\UidType;

readonly class CreatePagoFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private VoucherRepository $voucherRepository,
        private PagoFacturaRepository $pagoRepository,
        private ClienteRepository $clienteRepository,
    ) {}

    /**
     * @param array{
     *   facturaId: string,
     *   montoAplicado: float,
     *   voucherNumero: string,
     *   voucherNumeroOperacion: ?string,
     *   voucherMontoTotal: ?float,
     *   voucherFecha: ?string
     * } $data
     */
    public function execute(array $data): PagoFactura
    {
        $factura = $this->facturaRepository->ofId($data['facturaId'], true);
        $despacho = $factura->getDespacho();
        $cliente = $despacho?->getCliente();

        if (!$cliente) {
            throw new \RuntimeException('La factura no tiene cliente asociado.');
        }

        // Buscar o crear el voucher
        $voucher = $this->voucherRepository->findByNumeroAndCliente(
            $data['voucherNumero'],
            $cliente->getId()
        );

        if (!$voucher) {
            // Crear nuevo voucher
            if (empty($data['voucherMontoTotal'])) {
                throw new \RuntimeException('El monto total del voucher es requerido para un nuevo voucher.');
            }

            $voucher = new Voucher();
            $voucher->setNumero($data['voucherNumero']);
            $voucher->setNumeroOperacion($data['voucherNumeroOperacion'] ?? null);
            $voucher->setMontoTotal((float) $data['voucherMontoTotal']);
            $voucher->setFecha(new \DateTime($data['voucherFecha'] ?? 'today'));
            $voucher->setCliente($cliente);
            $this->voucherRepository->save($voucher);
        }

        // Validar que haya saldo suficiente en el voucher
        $montoRestante = $voucher->getMontoRestante();
        $montoAplicado = (float) $data['montoAplicado'];

        if ($montoAplicado <= 0) {
            throw new \RuntimeException('El monto a aplicar debe ser mayor a cero.');
        }

        if ($montoAplicado > $montoRestante + 0.001) {
            throw new \RuntimeException(
                sprintf('El monto a aplicar (%.2f) supera el saldo disponible del voucher (%.2f).', $montoAplicado, $montoRestante)
            );
        }

        $pago = new PagoFactura();
        $pago->setVoucher($voucher);
        $pago->setFactura($factura);
        $pago->setMontoAplicado($montoAplicado);

        $this->pagoRepository->save($pago);

        return $pago;
    }
}
