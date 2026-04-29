<?php

namespace App\apps\security\Controller;

use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\Auth\TokenCheckDto;
use App\apps\security\Service\JwtConfigService;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use App\shared\Api\AbstractApi;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/security')]
final class SecurityApi extends AbstractApi
{
    #[Route(path: '/login_token', methods: ['POST'])]
    public function loginToken(
        TokenCheckDto $tokenDto,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        AuthenticationSuccessHandler $successHandler,
    ): Response {
        $data = $jwtManager->parse($tokenDto->token);
        $user = $userRepository->ofId($data['id'], true);
        return $successHandler->handleAuthenticationSuccess($user);
    }

    #[Route(path: '/common/user', methods: ['GET'])]
    public function getCurrentUser(
        TokenStorageInterface $tokenStorage,
        UserDtoTransformer $userDtoTransformer,
    ): Response {
        $token = $tokenStorage->getToken();

        if (null === $token || !is_object($token->getUser())) {
            return $this->fail('Usuario no autenticado', code: Response::HTTP_UNAUTHORIZED);
        }

        $userDto = $userDtoTransformer->fromObject($token->getUser());

        return $this->ok(['item' => $userDto]);
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
