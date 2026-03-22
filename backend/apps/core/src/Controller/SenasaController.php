<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\SenasaScraping\SenasaScraperService;
use App\shared\Api\AbstractSerializerApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/senasa', name: 'api_senasa_')]
class SenasaController extends AbstractController
{
    public function __construct(
        private SenasaScraperService $senasaService,
        private LoggerInterface $logger
    ) {}

    #[Route('/verificar', name: 'verificar', methods: ['POST'])]
    public function verificar(Request $request): JsonResponse
    {
        try {
            // Obtener datos del request
            $data = json_decode($request->getContent(), true);

            if (!isset($data['codigo']) || !isset($data['fecha'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Faltan parámetros requeridos (codigo, fecha)'
                ], 400);
            }

            $codigo = trim($data['codigo']);
            $fecha = trim($data['fecha']);

            // Validar código
            if (empty($codigo) || strlen($codigo) < 3) {
                return $this->json([
                    'success' => false,
                    'error' => 'Código inválido'
                ], 400);
            }

            $this->logger->info('Verificando código SENASA', [
                'codigo' => $codigo,
                'fecha' => $fecha,
                'ip' => $request->getClientIp()
            ]);

            // Llamar al servicio
            $resultado = $this->senasaService->consultarLugarProduccion($codigo, $fecha);

            if ($resultado['error']) {
                return $this->json([
                    'success' => false,
                    'error' => $resultado['message']
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $resultado['data']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error en verificación SENASA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

}
