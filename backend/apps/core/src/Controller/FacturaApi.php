<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Factura\AnularFacturaService;
use App\apps\core\Service\Factura\CreateFacturaService;
use App\apps\core\Service\Factura\DeleteFacturaService;
use App\apps\core\Service\Factura\Dto\FacturaDto;
use App\apps\core\Service\Factura\Dto\FacturaDtoTransformer;
use App\apps\core\Service\Factura\Filter\FacturaFilterDto;
use App\apps\core\Service\Factura\GetFacturasByDespachoService;
use App\apps\core\Service\Factura\GetFacturasService;
use App\apps\core\Service\Factura\UpdateFacturaService;
use App\apps\core\Service\Factura\ExportReporteFacturacionService;
use App\apps\core\Service\Xml\XmlDocumentoParserService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/facturas')]
class FacturaApi extends AbstractSerializerApi
{
    #[Route('/', name: 'factura_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FacturaFilterDto $filterDto,
        GetFacturasService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'factura_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        FacturaDto $dto,
        CreateFacturaService $service,
        FacturaDtoTransformer $transformer,
    ): Response {
        $factura = $service->execute($dto);

        return $this->ok([
            'message' => 'Factura creada exitosamente',
            'item' => $transformer->fromObject($factura),
        ]);
    }

    #[Route('/by-despacho/{id}', name: 'factura_by_despacho', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function byDespacho(
        string $id,
        GetFacturasByDespachoService $service,
    ): Response {
        $items = $service->execute($id);

        return $this->ok(['items' => $items]);
    }

    #[Route('/export-reporte', name: 'factura_export_reporte', methods: ['GET'])]
    public function exportReporte(
        Request $request,
        ExportReporteFacturacionService $service,
    ): Response {
        $search = $request->query->get('search');
        return $service->execute($search ?: null);
    }

    #[Route('/parse-xml', name: 'factura_parse_xml', methods: ['POST'])]
    public function parseXml(
        Request $request,
        XmlDocumentoParserService $parser,
    ): Response {
        $file = $request->files->get('archivo');

        if (!$file) {
            return $this->fail('No se recibió ningún archivo');
        }

        try {
            $content = file_get_contents($file->getPathname());
            $data = $parser->parse($content);

            return $this->ok([
                'message' => 'XML procesado exitosamente',
                'item' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->fail('Error al procesar el XML: ' . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'factura_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        FacturaDtoTransformer $transformer,
        \App\apps\core\Repository\FacturaRepository $repository,
    ): Response {
        $factura = $repository->ofId($id, true);

        return $this->ok([
            'message' => 'Factura obtenida exitosamente',
            'item' => $transformer->fromObject($factura),
        ]);
    }

    #[Route('/{id}', name: 'factura_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        string $id,
        #[MapRequestPayload]
        FacturaDto $dto,
        UpdateFacturaService $service,
        FacturaDtoTransformer $transformer,
    ): Response {
        $factura = $service->execute($id, $dto);

        return $this->ok([
            'message' => 'Factura actualizada exitosamente',
            'item' => $transformer->fromObject($factura),
        ]);
    }

    #[Route('/{id}/anular', name: 'factura_anular', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function anular(
        string $id,
        AnularFacturaService $service,
        FacturaDtoTransformer $transformer,
    ): Response {
        $factura = $service->execute($id);

        return $this->ok([
            'message' => 'Factura anulada exitosamente',
            'item' => $transformer->fromObject($factura),
        ]);
    }

    #[Route('/{id}', name: 'factura_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        string $id,
        DeleteFacturaService $service,
    ): Response {
        $service->execute($id);

        return $this->ok([
            'message' => 'Factura eliminada exitosamente',
            'item' => null,
        ]);
    }
}
