<?php

namespace App\shared\Service;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

final readonly class ImageService
{
    public const BASE_FOLDER = '/upload';

    public function __construct(
        private CacheManager $cacheManager,
        private DataManager $dataManager,
        private FilterManager $filterManager,
    ) {
    }

    public function get(?string $path, string $filter): ?string
    {
        if (null === $path) {
            return null;
        }

        return $this->cacheManager->getBrowserPath(self::BASE_FOLDER.$path, $filter);
    }

    public function filter(?string $path, int $width, int $height, string $filter = 'custom'): ?string
    {
        if (null === $path) {
            return null;
        }

        $path = self::BASE_FOLDER.$path;
        if (!$this->cacheManager->isStored($path, $filter)) {
            $binary = $this->dataManager->find($filter, $path);

            $filteredBinary = $this->filterManager->applyFilter($binary, $filter, [
                'filters' => [
                    'thumbnail' => [
                        'size' => [$width, $height],
                    ],
                ],
            ]);

            $this->cacheManager->store($filteredBinary, $path, $filter);
        }

        return $this->cacheManager->resolve($path, $filter);
    }

    public function remove(?string $path): void
    {
        if (null === $path) {
            return;
        }

        $this->cacheManager->remove(self::BASE_FOLDER.$path);
    }
}