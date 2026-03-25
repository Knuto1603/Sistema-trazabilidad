<?php

namespace App\apps\core\Service\ArchivoDespacho\Dto;

use App\apps\core\Entity\ArchivoDespacho;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;

final readonly class ArchivoDespachoFactory
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private FacturaRepository $facturaRepository,
    ) {
    }

    public function ofDto(?ArchivoDespachoDto $dto): ?ArchivoDespacho
    {
        if (null === $dto) {
            return null;
        }

        $archivo = new ArchivoDespacho();
        $this->updateOfDto($dto, $archivo);

        return $archivo;
    }

    public function updateOfDto(ArchivoDespachoDto $dto, ArchivoDespacho $archivo): void
    {
        if ($dto->despachoId) {
            $despacho = $this->despachoRepository->ofId($dto->despachoId, true);
            $archivo->setDespacho($despacho);
        }

        if ($dto->facturaId) {
            $factura = $this->facturaRepository->ofId($dto->facturaId, true);
            $archivo->setFactura($factura);
        }

        $archivo->setNombre($dto->nombre);
        $archivo->setTipoArchivo($dto->tipoArchivo);
        $archivo->setRuta($dto->ruta);
        $archivo->setTamanho($dto->tamanho);
    }
}
