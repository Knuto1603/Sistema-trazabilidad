<?php

namespace App\shared\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

abstract class AppCache
{
    public const CACHE_TIME = 60;

    public function __construct(
        protected TagAwareCacheInterface $cache,
    ) {
    }

    public function get(string $key, callable $callback, array $tags = [], int $time = self::CACHE_TIME): mixed
    {
        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($callback, $tags, $time) {
                $item->tag(array_merge($this->tags(), $tags));
                $item->expiresAfter($time);

                return \call_user_func($callback);
            });
        } catch (InvalidArgumentException) {
        }

        return null;
    }

    public function delete(string $key): bool
    {
        try {
            return $this->cache->delete($key);
        } catch (InvalidArgumentException) {
        }

        return false;
    }

    public function deleteTags(): bool
    {
        try {
            return $this->cache->invalidateTags($this->tags());
        } catch (InvalidArgumentException) {
        }

        return false;
    }

    abstract public function tags(): array;

    protected function key(string $key): string
    {
        return md5($key);
    }
}
