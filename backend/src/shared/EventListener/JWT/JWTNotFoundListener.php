<?php

namespace App\shared\EventListener\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JWTNotFoundListener
{
    public function __invoke(JWTNotFoundEvent $event): void
    {
        $data = [
            'status' => false,
            'message' => 'Token no encontrado.',
        ];

        $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
