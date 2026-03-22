<?php

namespace App\shared\Service\Transformer;

interface DtoTransformerInterface
{
    public function fromObject(mixed $object): mixed;

    public function fromObjects(iterable|null $objects): iterable;
}
