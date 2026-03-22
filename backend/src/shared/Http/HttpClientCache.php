<?php

namespace App\shared\Http;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClientCache extends HttpClient
{
    private const TIME = 60;
    public function __construct(
        private readonly CacheInterface $appCache,
        HttpClientInterface $httpClient,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $urlApi, array $params = []): array
    {
        $key = md5($urlApi.json_encode($params));
        return $this->appCache->get($key, function (ItemInterface $item) use ($urlApi, $params) {
            $item->expiresAfter(static::TIME);

            return parent::get($urlApi, $params);
        });
    }
}
