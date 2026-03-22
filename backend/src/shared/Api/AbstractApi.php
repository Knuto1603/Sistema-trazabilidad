<?php

namespace App\shared\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApi
{
    public function ok(?array $data = null, ?string $message = null, int $code = Response::HTTP_OK): Response
    {
        return $this->json(true, $data, $message, $code);
    }

    public function fail(string $message, ?array $data = null, int $code = Response::HTTP_BAD_REQUEST): Response
    {
        return $this->json(false, $data, $message, $code);
    }

    public function json(bool $status, ?array $data, ?string $message, int $code, array $headers = []): Response
    {
        $params = ['status' => $status];

        if (null !== $message) {
            $params = [...$params, ...['message' => $message]];
        }

        if (null !== $data) {
            $params = [...$params, ...$data];
        }

        return $this->response($params, $code, $headers);
    }

    protected function response(array $params, int $code, array $headers): JsonResponse
    {
        return new JsonResponse($params, $code, $headers);
    }
}