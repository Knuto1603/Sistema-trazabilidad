<?php

namespace App\apps\core\Service\Voucher\Dto;

use App\shared\Service\Dto\DtoTrait;

final class VoucherDto
{
    use DtoTrait;

    public function __construct(
        public ?string $numero = null,
        public ?string $numeroOperacion = null,
        public ?float $montoTotal = null,
        public ?string $fecha = null,
        public ?string $clienteId = null,
        public ?string $clienteRazonSocial = null,
        public ?float $montoRestante = null,
        public ?float $montoUsado = null,
    ) {}
}
