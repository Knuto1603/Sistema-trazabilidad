<?php

namespace App\apps\security\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class JwtConfigService
{
    private const DEFAULT_TTL = 36000;
    private const MIN_TTL = 300;
    private const MAX_TTL = 604800;

    private string $configFile;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->configFile = $projectDir . '/var/jwt_config.json';
    }

    public function getTtl(): int
    {
        if (!is_file($this->configFile)) {
            return self::DEFAULT_TTL;
        }

        $data = json_decode((string) file_get_contents($this->configFile), true);

        return (int) ($data['ttl'] ?? self::DEFAULT_TTL);
    }

    public function setTtl(int $ttl): int
    {
        $ttl = max(self::MIN_TTL, min(self::MAX_TTL, $ttl));
        file_put_contents($this->configFile, json_encode(['ttl' => $ttl], JSON_PRETTY_PRINT));

        return $ttl;
    }
}
