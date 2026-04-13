<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\ArchivoDespacho\DeleteAllArchivosByDespachoService;
use App\apps\core\Service\ArchivoDespacho\DeleteArchivoDespachoService;
use App\apps\core\Service\ArchivoDespacho\Dto\ArchivoDespachoDtoTransformer;
use App\apps\core\Service\ArchivoDespacho\GetArchivosByDespachoService;
use App\apps\core\Service\ArchivoDespacho\UploadArchivoDespachoService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/archivos-despacho')]
class ArchivoDespachoApi extends AbstractSerializerApi
{
    #[Route('/upload', name: 'archivo_despacho_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        UploadArchivoDespachoService $service,
        ArchivoDespachoDtoTransformer $transformer,
    ): Response {
        $despachoId = $request->request->get('despachoId');
        $tipoArchivo = $request->request->get('tipoArchivo');
        $facturaId = $request->request->get('facturaId');
        $file = $request->files->get('archivo');

        if (!$despachoId || !$tipoArchivo || !$file) {
            return $this->fail('Faltan parámetros requeridos: despachoId, tipoArchivo, archivo');
        }

        try {
            $archivo = $service->execute($despachoId, $tipoArchivo, $file, $facturaId ?: null);

            return $this->ok([
                'message' => 'Archivo subido exitosamente',
                'item' => $transformer->fromObject($archivo),
            ]);
        } catch (\Exception $e) {
            return $this->fail('Error al subir el archivo: ' . $e->getMessage());
        }
    }

    #[Route('/by-despacho/{id}', name: 'archivo_despacho_by_despacho', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function byDespacho(
        string $id,
        GetArchivosByDespachoService $service,
    ): Response {
        $items = $service->execute($id);

        return $this->ok(['items' => $items]);
    }

    #[Route('/by-despacho/{id}/delete-all', name: 'archivo_despacho_delete_all', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_KNUTO')]
    public function deleteAll(
        string $id,
        DeleteAllArchivosByDespachoService $service,
    ): Response {
        $count = $service->execute($id);

        return $this->ok([
            'message' => "{$count} archivo(s) eliminados del despacho",
            'item' => null,
        ]);
    }

    #[Route('/{id}', name: 'archivo_despacho_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        string $id,
        DeleteArchivoDespachoService $service,
    ): Response {
        $service->execute($id);

        return $this->ok([
            'message' => 'Archivo eliminado exitosamente',
            'item' => null,
        ]);
    }
}
