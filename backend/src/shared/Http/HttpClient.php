<?php

namespace App\shared\Http;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClient
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
    ) {
    }

    public function get(string $urlApi, array $params): array
    {
        try {
            $response = $this->httpClient->request('GET', $urlApi, [
                'verify_peer' => false,
                'verify_host' => false,
                'query' => $params,
            ],
            );

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('URL Api Error ');
            }

            return $response->toArray();
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function post(string $urlApi, array $params): array
    {
        try {
            $response = $this->httpClient->request('POST', $urlApi, [
                'verify_peer' => false,
                'verify_host' => false,
                'json' => $params,
            ]);

            if (200 !== $response->getStatusCode()) {
                return ['status' => false, 'message' => 'URL Api Error '.$response->getStatusCode()];
            }

            return ['status' => true, 'data' => $response->toArray()];
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}