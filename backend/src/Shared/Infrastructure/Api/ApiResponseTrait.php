<?php

namespace App\Shared\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estandariza el formato de salida JSON para el Frontend.
 */
trait ApiResponseTrait
{
    protected function success(mixed $data, string $message = "Operación exitosa", int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $status);
    }

    protected function error(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
