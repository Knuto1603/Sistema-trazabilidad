<?php

namespace App\apps\core\Service\ArchivoDespacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeleteAllArchivosByDespachoService
{
    public function __construct(
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private DespachoRepository $despachoRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function execute(string $despachoUuid): int
    {
        $despacho = $this->despachoRepository->ofId($despachoUuid, true);

        $archivos = $this->archivoDespachoRepository->findByDespachoUuid($despachoUuid);

        foreach ($archivos as $archivo) {
            $rutaFisica = $this->projectDir . '/public/' . $archivo->getRuta();
            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }
        }

        $this->archivoDespachoRepository->deleteByDespacho($despacho);

        return count($archivos);
    }
}
