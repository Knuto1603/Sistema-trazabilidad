<?php

namespace App\apps\core\Controller;

use App\apps\core\Repository\UserSmtpConfigRepository;
use App\apps\core\Service\Smtp\ClearUserSmtpConfigService;
use App\apps\core\Service\Smtp\SaveUserSmtpConfigService;
use App\apps\core\Service\Smtp\UserSmtpConfigDto;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Api\DtoSerializer;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/smtp-config')]
#[IsGranted('ROLE_ADMIN')]
class UserSmtpConfigApi extends AbstractSerializerApi
{
    #[Route('/', name: 'smtp_config_list', methods: ['GET'])]
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
    public function get(
        string $userUuid,
        UserSmtpConfigRepository $repository,
    ): Response {
        $config = $repository->findByUserUuid($userUuid);

        return $this->ok([
            'item' => $config ? [
                'userUuid'     => $config->getUserUuid(),
                'smtpEmail'    => $config->getSmtpEmail(),
                'hasPassword'  => true,
            ] : null,
        ]);
    }

    #[Route('/{userUuid}', name: 'smtp_config_save', requirements: ['userUuid' => UidType::REGEX], methods: ['POST'])]
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
            'item'    => [
                'userUuid'    => $config->getUserUuid(),
                'smtpEmail'   => $config->getSmtpEmail(),
                'hasPassword' => true,
            ],
        ]);
    }

    #[Route('/{userUuid}', name: 'smtp_config_clear', requirements: ['userUuid' => UidType::REGEX], methods: ['DELETE'])]
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
}
