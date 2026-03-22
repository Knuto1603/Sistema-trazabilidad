<?php

namespace App\shared\EventListener\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JWTInvalidListener
{
    public function __invoke(JWTInvalidEvent $event): void
    {
        $data = [
            'status' => false,
            'message' => 'Token no valido.',
        ];

        $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
