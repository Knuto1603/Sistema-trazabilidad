<?php

namespace App\shared\EventListener\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationFailureListener
{
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $data = [
            'status' => false,
        ];

        $response = new JWTAuthenticationFailureResponse(
            'Credenciales incorrectas, verifique su usuario y contraseña',
            Response::HTTP_BAD_REQUEST, // Response::HTTP_UNAUTHORIZED
        );

        $response->setData($data);

        $event->setResponse($response);
    }
}
