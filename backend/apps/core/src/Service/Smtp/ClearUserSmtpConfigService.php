<?php

namespace App\apps\core\Service\Smtp;

use App\apps\core\Repository\UserSmtpConfigRepository;

final readonly class ClearUserSmtpConfigService
{
    public function __construct(
        private UserSmtpConfigRepository $repository,
    ) {
    }

    public function execute(string $userUuid): void
    {
        $config = $this->repository->findByUserUuid($userUuid);
        if ($config === null) {
            return;
        }

        $this->repository->remove($config);
    }
}
