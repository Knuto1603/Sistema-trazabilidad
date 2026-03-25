<?php

namespace App\apps\core\Service\ApispEru;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApispEruService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(APISPERU_TOKEN)%')]
        private string $token,
        private LoggerInterface $logger,
    ) {
    }

    public function consultarRuc(string $ruc): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                "https://dniruc.apisperu.com/api/v1/ruc/{$ruc}",
                ['query' => ['token' => $this->token]]
            );

            $data = $response->toArray(false);

            if (isset($data['success']) && $data['success'] === false) {
                throw new \RuntimeException('RUC no encontrado');
            }

            if (empty($data['ruc'])) {
                throw new \RuntimeException('RUC no encontrado');
            }

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Error consultando RUC en ApispEru', ['ruc' => $ruc, 'error' => $e->getMessage()]);
            throw new \RuntimeException('RUC no encontrado: ' . $e->getMessage());
        }
    }

    public function consultarDni(string $dni): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                "https://dniruc.apisperu.com/api/v1/dni/{$dni}",
                ['query' => ['token' => $this->token]]
            );

            $data = $response->toArray(false);

            if (isset($data['success']) && $data['success'] === false) {
                throw new \RuntimeException('DNI no encontrado');
            }

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Error consultando DNI en ApispEru', ['dni' => $dni, 'error' => $e->getMessage()]);
            throw new \RuntimeException('DNI no encontrado: ' . $e->getMessage());
        }
    }
}
