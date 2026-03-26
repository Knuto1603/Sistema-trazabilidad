<?php

namespace App\apps\core\Service\ArchivoDespacho;

use App\apps\core\Entity\ArchivoDespacho;
use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UploadArchivoDespachoService
{
    public function __construct(
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private DespachoRepository $despachoRepository,
        private FacturaRepository $facturaRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function execute(
        string $despachoUuid,
        string $tipoArchivo,
        UploadedFile $file,
        ?string $facturaUuid = null,
    ): ArchivoDespacho {
        if (!$file->isValid()) {
            throw new \RuntimeException('El archivo subido no es válido');
        }

        $despacho = $this->despachoRepository->ofId($despachoUuid, true);

        $directorio = $this->projectDir . '/public/uploads/facturacion/' . $despachoUuid . '/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombre = uniqid() . '_' . $file->getClientOriginalName();
        $tamanho = $file->getSize() ?: 0;
        $file->move($directorio, $nombre);

        $ruta = 'uploads/facturacion/' . $despachoUuid . '/' . $nombre;

        $archivo = new ArchivoDespacho();
        $archivo->setNombre($nombre);
        $archivo->setTipoArchivo($tipoArchivo);
        $archivo->setRuta($ruta);
        $archivo->setTamanho($tamanho);
        $archivo->setDespacho($despacho);

        if ($facturaUuid) {
            $factura = $this->facturaRepository->ofId($facturaUuid, true);
            $archivo->setFactura($factura);
        }

        $this->archivoDespachoRepository->save($archivo);

        return $archivo;
    }
}
