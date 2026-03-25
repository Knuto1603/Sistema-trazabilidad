<?php

namespace App\apps\core\Service\ArchivoDespacho\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;

final class ArchivoDespachoDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        public ?string $nombre = null,
        public ?string $tipoArchivo = null,
        public ?string $ruta = null,
        public ?int $tamanho = null,
        public ?string $despachoId = null,
        public ?string $facturaId = null,
    ) {
    }
}
