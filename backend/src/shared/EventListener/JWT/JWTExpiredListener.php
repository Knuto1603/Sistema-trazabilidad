<?php

namespace App\shared\EventListener\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JWTExpiredListener
{
    public function __invoke(JWTExpiredEvent $event): void
    {
        $data = [
            'status' => false,
            'message' => 'Token ha expirado, por favor vuelva a iniciar sesión.',
        ];

        $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
