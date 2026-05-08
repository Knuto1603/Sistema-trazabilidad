<?php

namespace App\apps\core\Repository;

use App\apps\core\Entity\UserSmtpConfig;
use App\shared\Doctrine\DoctrineEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<UserSmtpConfig>
 */
class UserSmtpConfigRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSmtpConfig::class);
    }

    public function findByUserUuid(string $userUuid): ?UserSmtpConfig
    {
        return $this->findOneBy(['userUuid' => $userUuid]);
    }
}
