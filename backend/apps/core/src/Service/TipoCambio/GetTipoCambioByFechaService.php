<?php

namespace App\apps\core\Service\TipoCambio;

use App\apps\core\Entity\TipoCambio;
use App\apps\core\Repository\TipoCambioRepository;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDto;
use App\apps\core\Service\TipoCambioSunat\TipoCambioSunatService;
use App\shared\Exception\NotFoundException;

final readonly class GetTipoCambioByFechaService
{
    public function __construct(
        private TipoCambioRepository $tipoCambioRepository,
        private TipoCambioSunatService $sunatService,
        private CreateOrUpdateTipoCambioService $createOrUpdateService,
    ) {
    }

    public function execute(string $fecha): TipoCambio
    {
        $fechaObj = new \DateTime($fecha);
        $tc = $this->tipoCambioRepository->findByFecha($fechaObj);

        if ($tc !== null) {
            return $tc;
        }

        // No está en BD: intentar obtenerlo de la API externa y guardarlo
        try {
            $data = $this->sunatService->obtenerTipoCambio($fecha);
        } catch (\Throwable $e) {
            throw new NotFoundException(\sprintf('No se encontró tipo de cambio para la fecha %s', $fecha));
        }

        // La API puede devolver la fecha del último día hábil si es fin de semana/feriado;
        // guardamos con la fecha real devuelta por la API para evitar duplicados
        $fechaReal = $data['fecha'] ?? $fecha;
        $dto = new TipoCambioDto(
            fecha: $fechaReal,
            compra: (float) $data['compra'],
            venta: (float) $data['venta'],
        );

        return $this->createOrUpdateService->execute($dto);
    }
}
