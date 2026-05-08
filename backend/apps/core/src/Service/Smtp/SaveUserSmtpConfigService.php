<?php

namespace App\apps\core\Service\Smtp;

use App\apps\core\Entity\UserSmtpConfig;
use App\apps\core\Repository\UserSmtpConfigRepository;
use App\shared\Service\SmtpEncryptionService;

final readonly class SaveUserSmtpConfigService
{
    public function __construct(
        private UserSmtpConfigRepository $repository,
        private SmtpEncryptionService $encryption,
    ) {
    }

    public function execute(string $userUuid, UserSmtpConfigDto $dto): UserSmtpConfig
    {
        $existing = $this->repository->findByUserUuid($userUuid);
        $isNew    = $existing === null;

        if ($isNew && ($dto->smtpPassword === null || trim($dto->smtpPassword) === '')) {
            throw new \RuntimeException('La contraseña SMTP es requerida al crear la configuración.');
        }

        $config = $existing ?? new UserSmtpConfig();
        $config->setUserUuid($userUuid);
        $config->setSmtpEmail($dto->smtpEmail);

        if (!empty($dto->smtpPassword)) {
            $config->setSmtpPasswordEncrypted($this->encryption->encrypt($dto->smtpPassword));
        }

        $config->enable();
        $this->repository->save($config);

        return $config;
    }
}
