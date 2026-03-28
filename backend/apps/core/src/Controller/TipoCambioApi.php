<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\TipoCambio\CreateOrUpdateTipoCambioService;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDto;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDtoTransformer;
use App\apps\core\Service\TipoCambio\GetTipoCambioByFechaService;
use App\apps\core\Service\TipoCambio\GetTipoCambiosService;
use App\apps\core\Service\TipoCambio\ImportarAnioService;
use App\apps\core\Service\TipoCambioSunat\TipoCambioSunatService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tipos-cambio')]
class TipoCambioApi extends AbstractSerializerApi
{
    #[Route('/', name: 'tipo_cambio_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetTipoCambiosService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/by-fecha/{fecha}', name: 'tipo_cambio_by_fecha', requirements: ['fecha' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function byFecha(
        string $fecha,
        GetTipoCambioByFechaService $service,
        TipoCambioDtoTransformer $transformer,
    ): Response {
        try {
            $tc = $service->execute($fecha);

            return $this->ok([
                'message' => 'Tipo de cambio encontrado',
                'item' => $transformer->fromObject($tc),
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/scrape-sunat', name: 'tipo_cambio_scrape_sunat', methods: ['GET'])]
    public function scrapeSunat(
        TipoCambioSunatService $sunatService,
    ): Response {
        try {
            $data = $sunatService->obtenerTipoCambio();

            return $this->ok([
                'message' => 'Tipo de cambio obtenido de SUNAT',
                'item' => $data,
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/importar-anio', name: 'tipo_cambio_importar_anio', methods: ['POST'])]
    public function importarAnio(
        \Symfony\Component\HttpFoundation\Request $request,
        ImportarAnioService $service,
    ): Response {
        try {
            $body  = json_decode($request->getContent(), true) ?? [];
            $desde = $body['desde'] ?? null;

            $result = $service->execute($desde);

            $completo = $result['proxima'] === null;
            $message  = $completo
                ? "Importación completada: {$result['importados']} registros guardados"
                : "Lote procesado: {$result['importados']} registros. Continuar desde {$result['proxima']}";

            return $this->ok(['message' => $message, 'item' => $result]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/debug-sunat-raw', name: 'tipo_cambio_debug_sunat_raw', methods: ['GET'])]
    public function debugSunatRaw(TipoCambioSunatService $sunatService): Response
    {
        try {
            return $this->ok($sunatService->debugInfo());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/', name: 'tipo_cambio_create_or_update', methods: ['POST'])]
    public function createOrUpdate(
        #[MapRequestPayload]
        TipoCambioDto $dto,
        CreateOrUpdateTipoCambioService $service,
        TipoCambioDtoTransformer $transformer,
    ): Response {
        $tc = $service->execute($dto);

        return $this->ok([
            'message' => 'Tipo de cambio guardado exitosamente',
            'item' => $transformer->fromObject($tc),
        ]);
    }
}
