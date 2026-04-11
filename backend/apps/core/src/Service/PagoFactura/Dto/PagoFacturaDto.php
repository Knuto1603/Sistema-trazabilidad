<?php

namespace App\apps\core\Service\PagoFactura\Dto;

use App\shared\Service\Dto\DtoTrait;

final class PagoFacturaDto
{
    use DtoTrait;

    public function __construct(
        public ?float $montoAplicado = null,
        public ?string $justificanteEliminacion = null,
        public ?string $createdAt = null,

        // Voucher info (embebido)
        public ?string $voucherId = null,
        public ?string $voucherNumero = null,
        public ?string $voucherNumeroOperacion = null,
        public ?float $voucherMontoTotal = null,
        public ?float $voucherMontoRestante = null,
        public ?float $voucherMontoUsado = null,
        public ?string $voucherFecha = null,
        public ?string $voucherClienteId = null,
    ) {}
}
