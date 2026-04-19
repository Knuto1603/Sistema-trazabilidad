<?php

namespace App\apps\security\Controller;

use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\Auth\TokenCheckDto;
use App\apps\security\Service\JwtConfigService;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/dev/jwt-config', methods: ['GET'])]
    #[IsGranted('ROLE_KNUTO')]
    public function getJwtConfig(JwtConfigService $jwtConfigService): JsonResponse
    {
        return new JsonResponse([
            'status' => true,
            'item'   => ['ttl' => $jwtConfigService->getTtl()],
        ]);
    }

    #[Route('/dev/jwt-config', methods: ['PUT'])]
    #[IsGranted('ROLE_KNUTO')]
    public function updateJwtConfig(Request $request, JwtConfigService $jwtConfigService): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $ttl  = (int) ($data['ttl'] ?? 0);

        if ($ttl <= 0) {
            return new JsonResponse(['status' => false, 'message' => 'TTL inválido.'], Response::HTTP_BAD_REQUEST);
        }

        $saved = $jwtConfigService->setTtl($ttl);

        return new JsonResponse([
            'status'  => true,
            'message' => 'TTL actualizado. Aplica al próximo inicio de sesión.',
            'item'    => ['ttl' => $saved],
        ]);
    }
}
