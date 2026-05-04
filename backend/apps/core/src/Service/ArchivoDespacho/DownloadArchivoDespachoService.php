<?php

namespace App\apps\core\Service\ArchivoDespacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DownloadArchivoDespachoService
{
    public function __construct(
        private ArchivoDespachoRepository $archivoDespachoRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function execute(string $archivoUuid): BinaryFileResponse
    {
        $archivo = $this->archivoDespachoRepository->ofId($archivoUuid, true);

        $filePath = $this->projectDir . '/public/' . $archivo->getRuta();
        $realPath = realpath($filePath);
        $uploadsDir = realpath($this->projectDir . '/public/uploads');

        if ($realPath === false || $uploadsDir === false || !str_starts_with($realPath, $uploadsDir . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException('Ruta de archivo no válida');
        }

        $response = new BinaryFileResponse($realPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $archivo->getNombre());

        return $response;
    }
}
