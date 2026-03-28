<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeleteFacturaService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function execute(string $id): void
    {
        $factura = $this->facturaRepository->ofId($id, true);

        $archivos = $this->archivoDespachoRepository->findByFactura($factura);
        foreach ($archivos as $archivo) {
            $rutaFisica = $this->projectDir . '/public/' . $archivo->getRuta();
            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }
            $this->archivoDespachoRepository->remove($archivo);
        }

        $this->facturaRepository->removeById($id);
    }
}
