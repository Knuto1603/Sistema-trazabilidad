<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;

final readonly class DeleteDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private FacturaRepository $facturaRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $despacho = $this->despachoRepository->ofId($id, true);

        $this->archivoDespachoRepository->deleteByDespacho($despacho);
        $this->facturaRepository->deleteByDespacho($despacho);
        $this->despachoRepository->removeById($id);
    }
}
