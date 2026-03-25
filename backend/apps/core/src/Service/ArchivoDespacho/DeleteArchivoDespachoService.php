<?php

namespace App\apps\core\Service\ArchivoDespacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeleteArchivoDespachoService
{
    public function __construct(
        private ArchivoDespachoRepository $archivoDespachoRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function execute(string $id): void
    {
        $archivo = $this->archivoDespachoRepository->ofId($id, true);

        $rutaFisica = $this->projectDir . '/public/' . $archivo->getRuta();
        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
        }

        $this->archivoDespachoRepository->remove($archivo);
    }
}
