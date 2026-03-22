<?php

namespace App\shared\Service\Transformer;

abstract class DtoTransformer implements DtoTransformerInterface
{
    public function fromObjects(iterable|null $objects): iterable
    {
        if (null === $objects) {
            return [];
        }

        return array_map(fn ($object) => $this->fromObject($object), iterator_to_array($objects));
    }
}
