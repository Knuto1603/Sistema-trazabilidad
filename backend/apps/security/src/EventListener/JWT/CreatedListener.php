<?php

namespace App\apps\security\EventListener\JWT;

use App\apps\security\Entity\User;
use App\apps\security\Service\Auth\UserLoginDtoTransformer;
use App\apps\security\Service\JwtConfigService;
use App\shared\Doctrine\UidType;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

final class CreatedListener
{
    public function __construct(
        private readonly JwtConfigService $jwtConfigService,
        private readonly UserLoginDtoTransformer $transformer,
    ) {}

    public function __invoke(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();
        $dto = $this->transformer->fromObject($user);

        $payload['id']       = UidType::toString($user->uuid());
        $payload['fullname'] = $dto->fullname ?? '';
        $payload['roles']    = $dto->roles ?? [];
        $payload['modules']  = $dto->modules ?? [];
        $payload['exp']      = time() + $this->jwtConfigService->getTtl();
        $event->setData($payload);
    }
}