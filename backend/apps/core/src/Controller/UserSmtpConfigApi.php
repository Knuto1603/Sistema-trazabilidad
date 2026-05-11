<?php

namespace App\apps\core\Controller;

use App\apps\core\Entity\UserSmtpConfig;
use App\apps\core\Repository\UserSmtpConfigRepository;
use App\apps\core\Service\Smtp\ClearUserSmtpConfigService;
use App\apps\core\Service\Smtp\SaveUserSmtpConfigService;
use App\apps\core\Service\Smtp\UserSmtpConfigDto;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Api\DtoSerializer;
use App\shared\Doctrine\UidType;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/smtp-config')]
final class UserSmtpConfigApi extends AbstractSerializerApi
{
    #[Route('/', name: 'smtp_config_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserSmtpConfigRepository $repository): Response
    {
        $configs = $repository->findAll();
        $items = array_map(
            static fn($c) => ['userUuid' => $c->getUserUuid(), 'smtpEmail' => $c->getSmtpEmail()],
            $configs
        );

        return $this->ok(['items' => $items]);
    }

    #[Route('/{userUuid}', name: 'smtp_config_get', requirements: ['userUuid' => UidType::REGEX], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function get(
        string $userUuid,
        UserSmtpConfigRepository $repository,
    ): Response {
        $config = $repository->findByUserUuid($userUuid);

        return $this->ok([
            'item' => $config ? $this->toDto($config) : null,
        ]);
    }

    #[Route('/{userUuid}', name: 'smtp_config_save', requirements: ['userUuid' => UidType::REGEX], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function save(
        Request $request,
        string $userUuid,
        SaveUserSmtpConfigService $service,
        DtoSerializer $serializer,
    ): Response {
        /** @var UserSmtpConfigDto $dto */
        $dto = $serializer->deserialize($request->getContent(), UserSmtpConfigDto::class);
        $config = $service->execute($userUuid, $dto);

        return $this->ok([
            'message' => 'Configuración SMTP guardada correctamente.',
            'item'    => $this->toDto($config),
        ]);
    }

    #[Route('/{userUuid}', name: 'smtp_config_clear', requirements: ['userUuid' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function clear(
        string $userUuid,
        ClearUserSmtpConfigService $service,
    ): Response {
        $service->execute($userUuid);

        return $this->ok([
            'message' => 'Configuración SMTP eliminada.',
            'item'    => null,
        ]);
    }

    #[Route('/me', name: 'smtp_config_me_get', methods: ['GET'])]
    public function getMe(
        TokenStorageInterface $tokenStorage,
        JWTTokenManagerInterface $jwtManager,
        UserSmtpConfigRepository $repository,
    ): Response {
        $myUuid = $this->resolveUuid($tokenStorage, $jwtManager);
        $config = $repository->findByUserUuid($myUuid);

        return $this->ok([
            'item' => $config ? $this->toDto($config) : null,
        ]);
    }

    #[Route('/me', name: 'smtp_config_me_save', methods: ['POST'])]
    public function saveMe(
        Request $request,
        TokenStorageInterface $tokenStorage,
        JWTTokenManagerInterface $jwtManager,
        SaveUserSmtpConfigService $service,
        DtoSerializer $serializer,
    ): Response {
        $myUuid = $this->resolveUuid($tokenStorage, $jwtManager);
        /** @var UserSmtpConfigDto $dto */
        $dto = $serializer->deserialize($request->getContent(), UserSmtpConfigDto::class);
        $config = $service->execute($myUuid, $dto);

        return $this->ok([
            'message' => 'Configuración guardada.',
            'item'    => $this->toDto($config),
        ]);
    }

    #[Route('/me', name: 'smtp_config_me_clear', methods: ['DELETE'])]
    public function clearMe(
        TokenStorageInterface $tokenStorage,
        JWTTokenManagerInterface $jwtManager,
        ClearUserSmtpConfigService $service,
    ): Response {
        $myUuid = $this->resolveUuid($tokenStorage, $jwtManager);
        $service->execute($myUuid);

        return $this->ok(['message' => 'Configuración eliminada.', 'item' => null]);
    }

    private function resolveUuid(TokenStorageInterface $tokenStorage, JWTTokenManagerInterface $jwtManager): string
    {
        $payload  = $jwtManager->decode($tokenStorage->getToken());
        $userUuid = \is_array($payload) ? ($payload['id'] ?? null) : null;

        if (!$userUuid) {
            throw new AccessDeniedException('No se pudo identificar al usuario autenticado.');
        }

        return $userUuid;
    }

    private function toDto(UserSmtpConfig $config): array
    {
        return [
            'userUuid'     => $config->getUserUuid(),
            'smtpEmail'    => $config->getSmtpEmail(),
            'hasPassword'  => $config->getSmtpPasswordEncrypted() !== '',
            'displayName'  => $config->getDisplayName(),
            'firmaNombre'  => $config->getFirmaNombre(),
            'firmaCargo'   => $config->getFirmaCargo(),
            'firmaEmpresa' => $config->getFirmaEmpresa(),
            'ccEmails'     => $config->getCcEmails(),
        ];
    }
}
