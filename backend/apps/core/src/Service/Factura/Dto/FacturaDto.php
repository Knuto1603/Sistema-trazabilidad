<?php

namespace App\apps\core\Service\Factura\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class FacturaDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        public ?string $tipoDocumento = null,

        #[Assert\NotBlank]
        public ?string $serie = null,

        #[Assert\NotBlank]
        public ?string $correlativo = null,

        public ?string $numeroDocumento = null,
        public ?string $numeroGuia = null,

        #[Assert\NotBlank]
        public ?string $fechaEmision = null,

        public string $moneda = 'USD',
        public ?string $detalle = null,
        public ?int $kgCaja = null,
        public ?string $unidadMedida = null,
        public ?int $cajas = null,
        public ?float $cantidad = null,
        public ?float $valorUnitario = null,
        public ?float $importe = null,
        public ?float $igv = null,
        public ?float $total = null,
        public ?float $tipoCambio = null,
        public ?string $tipoServicio = null,
        public ?string $tipoOperacion = null,
        public bool $isAnulada = false,
        public ?string $contenedor = null,

        #[Assert\NotBlank]
        #[Uid]
        public ?string $despachoId = null,

        public ?int $despachoNumero = null,
        public ?string $clienteRazonSocial = null,
    ) {
    }
}
