<?php

namespace App\apps\security\Controller;

use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\Auth\TokenCheckDto;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/security')]
final class SecurityApi
{
    #[Route(path: '/login_token', methods: ['POST'])]
    public function loginToken(
        TokenCheckDto $tokenDto,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        AuthenticationSuccessHandler $successHandler,
    ): Response {
        $data = $jwtManager->parse($tokenDto->token);
        $user = $userRepository->ofId($data['id']);
        return $successHandler->handleAuthenticationSuccess($user); // Return new token
    }

    #[Route(path: '/common/user', methods: ['GET'])]
    public function getCurrentUser(
        TokenStorageInterface $tokenStorage,
        UserDtoTransformer $userDtoTransformer
    ): JsonResponse {
        // Obtener el token del usuario autenticado
        $token = $tokenStorage->getToken();

        if (null === $token) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el usuario del token
        $user = $token->getUser();

        if (!is_object($user)) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Transformar el usuario a DTO para enviar solo los datos necesarios
        $userDto = $userDtoTransformer->fromObject($user);

        return new JsonResponse($userDto);
    }

}
